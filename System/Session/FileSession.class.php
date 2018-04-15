<?php
/**
 * Interface FileSession | ZedBoot/System/Session/FileSession.class.php
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
		$defaultExpiry=28800; //8 hours
	protected
		$errorLogger=null,
		$fsTools=null,
		$expiry=null,
		$error=null,
		$sessionId=null,
		$savePath=null,
		$dataPath=null,
		$metaPath=null,
		$started=false,
		$gcChance=null,
		$gcLifetime=null;
	public function getError(){ return $this->error; }
	/**
	 * @param $savePath String root directory for files
	 * @param $sessionId String unique to session
	 * @param $errorLogger \ZedBoot\System\Error\ErrorLoggerInterface
	 * @param $gcLifetime int garbage collection lifetime in seconds, minimum 3600
	 * @param $gcChance int garbage collection probability (calculated as 1 in $gcChance), if 0, garbage collection will not run
	 */
	public function __construct($savePath, $sessionId, \ZedBoot\System\Error\ErrorLoggerInterface $errorLogger, $gcLifetime=86400, $gcChance=500)
	{
		$this->errorLogger=$errorLogger;
		$this->savePath=$savePath;
		$this->sessionId=$sessionId;
		$this->fsTools=new \ZedBoot\System\Utilities\FileSystemTools($this->savePath,$this->errorLogger);
		$this->expiry=static::$defaultExpiry;
		if(!is_int($gcChance) || $gcChance<0)
		{
			throw new Exception('Parameter $gcChance must be an integer >= 0.');
		}
		else $this->gcChance=$gcChance;
		$ok=(!is_int($gcLifetime) || $gcLifetime<3600)
		{
			throw new Exception('Parameter $gcLifetime must be an integer >= 3600.');
		}
		else $this->gcLifetime=$gcLifeTime;
	}
	protected function checkExpiry($seconds)
	{ return is_int($seconds) && ($seconds>=30 || $seconds===0) || $this->errorLogger->setError($this->error='invalid expiry;'.json_encode($seconds).', must be 0 or at least 30 seconds',\E_USER_WARNING); }
	/**
	 * Default expiry is 8 hours (28800 seconds)
	 */
	public function setExpiry($seconds)
	{
		$ok=true;
		if($ok) $ok=$this->checkExpiry($seconds);
		if($ok) $this->expiry=$seconds;
		return $ok;
	}
	public function getDataStore($key,$expiry=null,$forceCreate=true)
	{
		$ok=true;
		$result=false;
		$dataStore=null;
		$keyPath=null;
		if($ok && !($ok=!empty($this->sessionId))) $this->errorLogger->setError($this->error='session id not set',\E_USER_WARNING);
		if($ok && !$this->started) $ok=$this->start();
		if($ok && is_null($expiry)) $expiry=$this->expiry;
		if($ok) $ok=$this->checkExpiry($expiry);
		if($ok) $ok=false!==($keyPath=$this->processKey($key));
		if($ok) $ok=(false!==($dataStore=$this->getExpirableDataStore($keyPath,$expiry,$forceCreate)));
		if($ok) $result=$dataStore;
		return $result;
	}
	protected function processKey($key)
	{
		$result=false;
		$ok=true;
		$parts=null;
		if($ok)
		{
			$key=trim($key,'/');
			if(!($ok=!empty($key))) $this->errorLogger->setError($this->error='invalid (empty) key',\E_USER_WARNING);
		}
		if($ok)
		{
			$parts=explode('/',$key);
			foreach($parts as &$part) if(!($ok=ctype_alnum($part)))
			{
				$this->errorLogger->setError($this->error='invalid key: expected alphanumeric segments delimited by \'/\'',\E_USER_WARNING,'invalid key: expected alphanumeric segments delimited by \'/\':'.$key);
				break;
			}
		}
		if($ok) $result=implode('.subs/',$parts);
		return $result;
	}
	protected function start()
	{
		$ok=true;
		$fp=null;
		$locked=false;
		$metaStore=null;
		$metaData=null;
		$mtime=null;
		$time=time();
		if($ok) $ok=$this->prepSessionId();
		if($ok) $ok=false!==($metaStore=$this->lockAndLoadDS($this->metaPath));
//Begin critical section
		if($ok)
		{
			$ok=(false!==($mtime=filemtime($this->metaPath)));
			if(!$ok) $this->errorLogger->setError($this->error='unable to get modified time for lock file',\E_USER_WARNING,'unable to get mtime for '.$this->metaPath);
		}
		if($ok && !($ok=$metaStore->read($metaData))) $this->error=$metaStore->getError();
		if($ok && !is_array($metaData)) $metaData=array();
		if($ok && $this->expiry!==0 && $this->expiry<($time-$mtime)) //expiry value of 0 means never
		{
			//The session has expired - reset everything
			$metaData['exp_by_key']=array();
			$metaData['key_rotation']=array();
			$ok=$this->resetFiles();
		}
		if($ok) $ok=$this->clearExpiredKeys($metaData,$time);
		if($ok && !$ok=(is_dir($this->dataPath) || mkdir($this->dataPath,0700))) $this->errorLogger->setError($this->error='unable to create data folder',\E_USER_WARNING,'unable to create data folder: '.$this->dataPath);
		if($ok && !($ok=$metaStore->write($metaData))) $this->error=$metaStore->getError();
//End critical section
		if($locked) $ok=$this->unlockDS($metaStore,$ok);
		if($ok && $this->gcChance>0 && rand(0,$this->gcChance-1)==floor($this->gcChance/2)) $ok=$this->gc();
		return $ok;
	}
	protected function prepSessionId()
	{
		$ok=true;
		if($ok && !($ok=ctype_alnum($this->sessionId))) $this->errorLogger->setError($this->error='session id must be alphanumeric',\E_USER_WARNING);
		if($ok)
		{
			$this->dataPath=$this->savePath.'/'.$this->sessionId.'.data';
			//Creation and deletion of data and subfolders happens while this file is locked
			$this->metaPath=$this->savePath.'/'.$this->sessionId.'.meta';
		}
		return $ok;
	}	
	protected function clearExpiredKeys(&$metaData,$time)
	{
		$ok=true;
		if(empty($metaData['exp_by_key'])) $metaData['exp_by_key']=array();
		if(empty($metaData['key_rotation'])) $metaData['key_rotation']=array();
		$expByKey=&$metaData['exp_by_key'];
		$keyRotation=&$metaData['key_rotation'];
		$c=count($keyRotation);
		//Clean up expired items
		//check one fifth-ish of keys, minimum of 1, up to 10
		$c=$c>0?(min(floor($c/5+1),10)):0;
		for($i=0; $i<$c; $i++)
		{
			//get the first key off the queue
			$k=array_shift($keyRotation);
			if(array_key_exists($k,$expByKey)) //sanity check
			{
				if($expByKey[$k]!==0 && $expByKey[$k]<$time) //Expiry=0 indicates no expiry
				{
					$p=$this->dataPath.'/'.$k;
					//key is expired - remove data file and expiry index
					unset($expByKey[$k]);
					if(file_exists($p) && !($ok=unlink($p))) $this->errorLogger->setError('could not remove data file',\E_USER_WARNING,'could not remove data file '.$p);
				}
				//key is ok - put it at the back of the queue
				else $keyRotation[]=$k;
			}
		}
		return $ok;
	}

	protected function getExpirableDataStore($subPath,$expiry,$forceCreate)
	{
		$metaStore=null;
		$metaData=null;
		$dataStore=null;
		$ok=true;
		$locked=true;
		$result=false;
		//Possible scenarios
		//$forceCreate | datastore expired | $loadDS
		//   true          yes                true
		//   true          no                 true
		//   false         yes                false
		//   false         no                 true  **
		// ** Only one case where $loadDS doesn't coincide with $forceCreate - it will be handled accordingly
		$loadDS=$forceCreate;
		$time=time();
		//It is possible for the .meta file to be deleted during this process,
		//but it should have been renewed by start(),
		//so if the script has lasted long enough for it to expire there is a bigger problem
		if($ok) $ok=false!==($metaStore=$this->lockAndLoadDS($this->metaPath));
//Begin critical section
		if($ok && !($ok=$metaStore->read($metaData))) $this->error=$metaStore->getError();
		if($ok)
		{
			if(!is_array($metaData)) $metaData=array();
			if(!array_key_exists('exp_by_key',$metaData)) $metaData['exp_by_key']=array();
			if(!array_key_exists('key_rotation',$metaData)) $metaData['key_rotation']=array();
			$expByKey=&$metaData['exp_by_key'];
			$keyRotation=&$metaData['key_rotation'];
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
					if(file_exists($p) && !($ok=unlink($p))) $this->errorLogger->setError('could not remove data file',\E_USER_WARNING,'could not remove data file '.$p);
				}
				//Forced creation is disabled, but the datastore exists, so load it
				else $loadDS=true;
				//key is still in the rotation, leave it there - cleanup is handled by clearExpiredKeys()
			}
			else if($loadDS) $keyRotation[]=$subPath; //Put key into rotation
		}
		if($ok && $loadDS)
		{
			//Update expiry time, 0 for no expiry
			$expByKey[$subPath]=($expiry===0?0:$time+$expiry);
			if(!($ok=$metaStore->write($metaData))) $this->errorLogger->setError($this->error=$metaStore->getError(),\E_USER_WARNING);
		}
		if($ok)
		{
			if($loadDS)
			{
				$dataStore=new \ZedBoot\System\DataStore\FileDataStore($this->dataPath.'/'.$subPath,$this->errorLogger);
			}
			else $dataStore=null; //$forceCreate==false and datastore is nonexistent or expired
		}
//End critical section
		if($locked) $ok=$this->unlockDS($metaStore,$ok);
		if($ok) $result=$dataStore;
		return $result;
	}
	/**
	 * Run garbage collection
	 * Will be handled automatically if $gcChance parameter to constructor is non-zero
	 * @param $lifetime int (optional) default is 24 hours (86400 seconds)
	 */
	public function gc($lifetime=null)
	{
		$ok=true;
		$time=null;
		$files=null;
		$toCheck=array();
		if(empty($lifetime)) $lifetime=$this->gcLifetime;
		if($ok && !($ok=(is_numeric($lifetime) && $lifetime>=3600))) $this->errorLogger->setError($this->error='invalid lifetime, must be at least 3600 seconds',\E_USER_WARNING);
		if($ok)
		{
			$time=time();
			if(!$ok=(false!==($files=glob($this->savePath.'/*.meta')))) $this->errorLogger->setError($this->error='unable to search session directory',\E_USER_WARNING,'glob('.$this->savePath.'/*.meta) failed');
		}
		if($ok) foreach($files as $file)
		{
			if(!$ok=(false!==($mt=filemtime($file)))) $this->errorLogger->setError($this->error='unable to get modified time for meta file',\E_USER_WARNING,'filemtime('.$this->dataPath.'/'.$file.') failed');
			if($ok && $time-$mt>$lifetime) $toCheck[]=basename($file,'.meta');
			if(!$ok) break;
		}
		if($ok) foreach($toCheck as $name) if(!($ok=$this->gcProcessSession($this->savePath.'/'.$name.'.meta',$this->savePath.'/'.$name.'.data',$time,$lifetime))) break;
		return $ok;
	}
	protected function gcProcessSession($metaPath,$dataPath,$time,$lifetime)
	{
		$metaStore=null;
		$mtime=null;
		$remove=false;
		$ok=true;
		
		if($ok) $ok=false!==($metaStore=$this->lockAndLoadDS($metaPath));
//Begin critical section
		//check modified time again in case something happened in the meantime
		if($ok && !$ok=(false!==($mtime=filemtime($metaPath)))) $this->errorLogger->setError($this->error='unable to get modified time for lock file',\E_USER_WARNING,'unable to get mtime for '.$metaPath);
		if($ok)
		{
			$remove=$time-$mtime>$lifetime;
			//if file hasn't been modified, recursively remove .data directory
			if($remove && is_dir($dataPath) && !$this->fsTools->rmdirRecursive($dataPath))
			{
				$ok=false;
				$this->error=$this->fsTools->getError();
			}
		}
//End critical section
		if($locked) $ok=$this->unlockDS($metaStore,$ok);
		if($ok && $remove && !$ok=(unlink($metaPath))) $this->errorLogger->setError($this->error='unable to remove meta file',\E_USER_WARNING,'unable to remove meta file'.$metaPath);
		return $ok;
	}
	protected function lockAndLoadDS($path)
	{
		$ok=true;
		$result=false;
		$ds=null;
		if($ok)
		{
			$ds=new \ZedBoot\System\DataStore\FileDataStore($path,$this->errorLogger);
			if(!($ok=$locked=$ds->lock())) $this->error=$ds->getError();
		}
		if($ok) $result=$ds;
		return $result;
	}
	/**
	 * @param DataStore $dataStore to unlock
	 * @param boolean $ok current error status, if already false, no error will be reported
	 */
	protected function unlockDS($dataStore,$ok)
	{
		//Attempt to unlock whether 
		if(!$dataStore->unlock() && $ok)
		{
			$ok=false;
			$this->error=$dataStore->getError();
		}
		return $ok;
	}
}
