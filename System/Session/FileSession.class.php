<?php
/**
 * Class FileSession | ZedBoot/System/Session/FileSession.class.php
 * @license     GNU General Public License, version 3
 * @package     System
 * @subpackage  Session
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2016-2018 Jonathan Hulka
 */

/**
 * SessionInterface implementation
 * Produces instances of \ZedBoot\System\DataStore\FileDataStore
 * For optimal performance use on a ram disk or tmpfs drive, but pay attention to file size/block size ratio (you could waste a lot of space creating small files with 4k blocks on tmpfs).
 */
namespace ZedBoot\System\Session;
class FileSession implements \ZedBoot\System\Session\SessionInterface
{
	protected static
		$defaultGCChance=500, //1 in 500
		$defaultExpiry=28800; //8 hours
	protected
		$fsTools=null,
		$expiry=null,
		$error=null,
		$sessionId=null,
		$savePath=null,
		$dataPath=null,
		$metaPath=null,
		$started=false,
		$gcChance=null;
	public function getError(){ return $this->error; }
	/**
	 * If either $gcChance or $expiry is 0, garbage collection will not run
	 * @param $savePath String root directory for files
	 * @param $sessionId String unique to session. Each session will be indexed and garbage collected separately.
	 * @param $expiry int expiry (seconds - default 28800) for:
	 *   - datastore creation (default - can be specified in getDataStore() parameters) - datastores older than this will be reset
	 *   - and garbage collection - datastores could be cleaned up after max($expiry,3600) seconds
	 * @param $gcChance int garbage collection probability (calculated as 1 in $gcChance - default 500), if 0, garbage collection will not run
	 */
	public function __construct($savePath, $sessionId, $expiry=null, $gcChance=null)
	{
		$this->savePath=$savePath;
		$this->sessionId=$sessionId;
		$this->fsTools=new \ZedBoot\System\Utilities\FileSystemTools($this->savePath);
		$this->expiry=($expiry===null)?static::$defaultExpiry:$expiry;
		$this->gcChance=($gcChance===null)?static::$defaultGCChance:$gcChance;
		$this->checkExpiry($this->expiry);
		if(!is_int($this->gcChance) || $this->gcChance<0) throw new Exception('Parameter $gcChance must be an integer >= 0.');
	}
	protected function checkExpiry($expiry)
	{
		if(!is_int($expiry) || ($expiry<30 && $expiry!==0)) throw new \Exception('Invalid expiry: '.json_encode($expiry).', must be 0 or at least 30 seconds.');
	}
	public function getDataStore($key,$expiry=null,$forceCreate=true)
	{
		$result=false;
		$dataStore=null;
		$keyPath=null;
		try
		{
			if(empty($this->sessionId)) throw new \Exception('Session id not set');
			if(!$this->started) $this->start();
			if(is_null($expiry)) $expiry=$this->expiry;
			$this->checkExpiry($expiry);
			$keyPath=$this->processKey($key);
			$result=$this->getExpirableDataStore($keyPath,$expiry,$forceCreate);
		}
		catch(\Exception $e)
		{
			error_log($e);
			$this->error=$e->getMessage();
		}
		return $result;
	}
	protected function processKey($key)
	{
		$parts=null;
		$key=trim($key,'/');
		if(empty($key)) throw new \Exception('Invalid (empty) key.');
		$parts=explode('/',$key);
		foreach($parts as &$part) if(!ctype_alnum($part)) throw new \Exception('Invalid key: expected alphanumeric segments delimited by \'/\'');
		return implode('.subs/',$parts);
	}
	protected function start()
	{
		$fp=null;
		$metaStore=null;
		$metaData=null;
		$mtime=null;
		$time=time();		
		$this->prepSessionId();
//Begin critical section
		$metaStore=new \ZedBoot\System\DataStore\FileDataStore($this->metaPath);
		if(!$metaStore->lock()) throw new \Exception('System error: Unable to lock meta store.');
		if(false===($mtime=filemtime($this->metaPath))) throw new \Exception('System error: unable to get mtime for '.$this->metaPath);
		if(!$metaStore->read($metaData)) throw new \Exception('System error: could not read meta data.');
		if(!is_array($metaData)) $metaData=array();
		$this->clearExpiredKeys($metaData,$time);
		if(!(is_dir($this->dataPath) || mkdir($this->dataPath,0700))) throw new \Exception('System error: unable to create data folder: '.$this->dataPath);
		if(!$metaStore->writeAndUnlock($metaData)) throw new \Exception('System error: could not write/unlock meta data.');
//End critical section
		if($this->expiry>0 && $this->gcChance>0 && rand(0,$this->gcChance-1)==floor($this->gcChance/2) && !$this->gc(max($this->expiry,3600))) throw new \Exception('System error: gc failed.');
	}
	protected function prepSessionId()
	{
		if(!ctype_alnum($this->sessionId)) throw new \Exception('Session id must be alphanumeric',\E_USER_WARNING);
		$this->dataPath=$this->savePath.'/'.$this->sessionId.'.data';
		//Creation and deletion of data and subfolders happens while this file is locked
		$this->metaPath=$this->savePath.'/'.$this->sessionId.'.meta';
	}	
	protected function clearExpiredKeys(&$metaData,$time)
	{
		if(empty($metaData['exp_by_key'])) $metaData['exp_by_key']=array();
		$expByKey=&$metaData['exp_by_key'];
		$c=count($expByKey);
		//Clean up expired items
		//check one fifth-ish of keys, up to 10
		$c=$c>0?(min(floor($c/5+1),10)):0;
		for($i=0; $i<$c; $i++)
		{
			//get the first key off the queue
			reset($expByKey);
			$k=key($expByKey);
			$t=array_shift($expByKey);
			if($t!==0 && $t<$time) //Expiry=0 indicates no expiry
			{
				$p=$this->dataPath.'/'.$k;
				if(file_exists($p) && !unlink($p)) throw new \Exception('System error: Could not remove data file '.$p);
			}
			//key is ok - put it at the back of the queue
			else $expByKey[$k]=$t;
		}
	}

	protected function getExpirableDataStore($subPath,$expiry,$forceCreate)
	{
		$metaStore=null;
		$metaData=null;
		$locked=true;
		$result=false;
		//Possible scenarios
		//$forceCreate | datastore expired | $loadDS
		//   true          yes                true     expired, but we are re-creating it
		//   true          no                 true     not expired
		//   false         yes                false    expired, not creating
		//   false         no                 true  ** not expired
		// ** Only one case where $loadDS doesn't coincide with $forceCreate - it will be handled accordingly
		$loadDS=$forceCreate;
		$time=time();
		//It is possible for the .meta file to be deleted during this process,
		//but it should have been renewed by start(),
		//so if the script has lasted long enough for it to expire there is a bigger problem
		$metaStore=new \ZedBoot\System\DataStore\FileDataStore($this->metaPath);
//Begin critical section
		if(!$metaStore->lockAndRead($metaData)) throw new \Exception('System error: Unable to lock/read meta store.');
		if(!is_array($metaData)) $metaData=array();
		if(!array_key_exists('exp_by_key',$metaData)) $metaData['exp_by_key']=array();
		$expByKey=&$metaData['exp_by_key'];
		if(array_key_exists($subPath,$expByKey))
		{
			//This key has been used - make sure it isn't expired
			//if it is expired clean up so the datastore will start fresh
			//Should be safe, since this critical section is the only place these file data stores are created,
			//and if has expired, whoever was using it last had better be done by now
			if($expByKey[$subPath]!==0 && $time>$expByKey[$subPath]) //Expiry===0 indicates no expiry
			{
				//DataStore has expired, clean up the file
				$p=$this->dataPath.'/'.$subPath;
				if(file_exists($p) && !unlink($p)) throw new \Exception('System error: Could not remove data file '.$p);
			}
			//Datastore exists, so load it
			else $loadDS=true;
		}
		if($loadDS)
		{
			//Update expiry time, 0 for no expiry
			$expByKey[$subPath]=($expiry===0?0:$time+$expiry);
			if(!$metaStore->write($metaData)) throw new \Exception('System error: Could not write meta store.');
			$result=new \ZedBoot\System\DataStore\FileDataStore($this->dataPath.'/'.$subPath);
		}
		else $result=null; //$forceCreate==false and datastore is nonexistent or expired
		if(!$metaStore->unlock()) throw new \Exception('System error: Could not unlock meta store.');
//End critical section
		return $result;
	}
	/**
	 * Run garbage collection
	 * Will be handled automatically if $gcChance parameter to constructor is non-zero
	 * @param $lifetime int (optional) default is 8 hours (28800 seconds)
	 */
	public function gc($lifetime=null)
	{
		$result=false;
		try
		{
			$mt=null;
			$time=null;
			$files=null;
			$toCheck=array();
			if(empty($lifetime)) $lifetime=static::$defaultExpiry;
			if(!is_numeric($lifetime) || $lifetime<3600) throw new \Exception('Invalid lifetime, must be at least 3600 seconds');
			$time=time();
			if(false===($files=glob($this->savePath.'/*.meta'))) throw new \Exception('System error: Unable to search session directory: glob('.$this->savePath.'/*.meta) failed.');
			foreach($files as $file)
			{
				if(false===($mt=filemtime($file))) throw new \Exception('System error; Unable to get modified time for meta file: filemtime('.$this->dataPath.'/'.$file.') failed');
				if($time-$mt>$lifetime) $toCheck[]=basename($file,'.meta');
			}
			foreach($toCheck as $name) $this->gcProcessSession($this->savePath.'/'.$name.'.meta',$this->savePath.'/'.$name.'.data',$time,$lifetime);
			$result=true;
		}
		catch(\Exception $e)
		{
			error_log($e);
			$this->error=$e->getMessage();
		}
		return $result;
	}
	protected function gcProcessSession($metaPath,$dataPath,$time,$lifetime)
	{
		$metaStore=null;
		$mtime=null;
		$remove=false;
		$metaStore=new \ZedBoot\System\DataStore\FileDataStore($this->metaPath);
//Begin critical section
		if(!$metaStore->lock()) throw new \Exception('System error: Unable to lock meta store.');
		//check modified time again in case something happened in the meantime
		if(false===($mtime=filemtime($metaPath))) throw new \Exception('System error: Unable to get mtime for '.$metaPath);
		$remove=$time-$mtime>$lifetime;
		//if file hasn't been modified, recursively remove .data directory
		if($remove && is_dir($dataPath) && !$this->fsTools->rmdirRecursive($dataPath)) throw new \Exception($fsTools->getError());
		if(!$metaStore->unlock()) throw new \Exception('System error: Could not unlock meta store.');
//End critical section
		if($remove && !unlink($metaPath)) throw new \Exception('System error: Unable to remove meta file '.$metaPath);
	}
}
