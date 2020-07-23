<?php
/**
 * Class FileSession | ZedBoot/Session/FileSession.class.php
 * @license     GNU General Public License, version 3
 * @package     Session
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2016-2020 Jonathan Hulka
 */

/**
 * SessionInterface implementation
 * Produces instances of \ZedBoot\DataStore\FileDataStore
 * Session expiry is guaranteed. If an expired session is used, all data is cleared first.
 * !!!Race conditions could occur if page load time exceeds session expiry.
 * For optimal performance use on a ram disk or tmpfs drive, but pay attention to file size/block size ratio (you could waste a lot of space creating small files with 4k blocks on tmpfs).
 */
namespace ZedBoot\Session;
use \ZedBoot\Error\ZBError as Err;
class FileSession implements \ZedBoot\Session\SessionInterface
{
	protected static
		$defaultGCChance=500, //1 in 500
		$defaultExpiry=28800; //8 hours
	protected
		$fsTools=null,
		$expiry=null,
		$sessionId=null,
		$savePath=null,
		$dataPath=null,
		$metaStore=null,
		$started=false,
		$gcChance=null,
		$gc=null;
	/**
	 * @param $savePath String root directory for files
	 * @param $sessionId String unique to session. Each session will be indexed and garbage collected separately.
	 * @param $expiry int expiry (seconds - default 28800, 0 for no expiry by default) for:
	 *   - datastore creation (default - can be specified in getDataStore() parameters) - datastores older than this will be reset
	 *   - and garbage collection - inactive datastores or sessions will be cleaned up some time after $expiry seconds
	 * @param $gcChance int garbage collection probability (calculated as 1 in $gcChance - default 500), if 0, garbage collection will not run
	 */
	public function __construct(string $savePath, string $sessionId, int $expiry = null, int $gcChance = null)
	{
		$this->savePath=$savePath;
		$this->sessionId=$sessionId;
		$this->expiry=(is_numeric($expiry))?$expiry:static::$defaultExpiry;
		$this->gcChance=($gcChance===null)?static::$defaultGCChance:$gcChance;
		$this->checkExpiry($this->expiry);
		if(!is_int($this->gcChance) || $this->gcChance<0) throw new Err('Parameter $gcChance must be an integer >= 0.');
	}
	protected function checkExpiry($expiry)
	{
		if(!is_numeric($expiry) || ($expiry<30 && $expiry!==0)) throw new Err('Invalid expiry: '.json_encode($expiry).', must be 0 or at least 30 seconds.');
	}
	public function getDataStore(string $key,int $expiry=null, bool $forceCreate=true): ? \ZedBoot\DataStore\DataStoreInterface
	{
		$result=null;
		$dataStore=null;
		$keyPath=null;
		if(empty($this->sessionId)) throw new Err('Session id not set');
		if(!$this->started) $this->start();
		if(is_null($expiry))
		{
			$expiry=$this->expiry;
		}
		else $this->checkExpiry($expiry);
		if($expiry>$this->expiry) throw new Err('Invalid expiry: '.json_encode($expiry).', must not be greater than session expiry of '.$this->expiry.'.');
		$keyPath=$this->processKey($key);
		$result=$this->getExpirableDataStore($keyPath,$expiry,$forceCreate);
		return $result;
	}

	public function clearAll(string $keyRoot='')
	{
		$metaData=null;
		if(!$this->started) $this->start();
		//It is possible for the .meta file to be deleted while locking,
		//but it should have been renewed by start()
//Begin critical section
		$metaData=$this->metaStore->lockAndRead();
		$dirty = false;
		if(is_array($metaData) && array_key_exists('exp_by_key',$metaData))
		{
			if($keyRoot === '')
			{
				if(is_dir($this->dataPath)) $this->rmdirRecursive($this->dataPath);
				$metaData['exp_by_key']=[];
				$dirty = true;
			}
			else
			{
				//Keys stored in meta data always start with '/'
				$keyPath='/'.$this->processKey($keyRoot);
				$fullPath=$this->dataPath.$keyPath;
				$metaData['exp_by_key']=$this->clearMetaSubPaths($metaData['exp_by_key'],$keyPath, $dirty);
				if(is_dir($fullPath.'.subs')) $this->rmdirRecursive($fullPath.'.subs');
				if(file_exists($fullPath)) unlink($fullPath);
			}
		}
//End critical section
		if($dirty)
		{
			$this->metaStore->writeAndUnlock($metaData);
		}
		else $this->metaStore->unlock();
	}

	public function refreshAll(string $keyRoot = '', int $expiry = null)
	{
		if(is_null($expiry))
		{
			$expiry = $this->expiry;
		}
		$metaData = null;
		$time = time();
		$expTime = ($expiry === 0 ? 0 : $time + $expiry);
		$expByKey = null;
		$keyPath = null;
		$subsPath = null;
		$subsPathLen = null;
		$dirty = false;
//Begin critical section
		$metaData = $this->metaStore->lockAndRead();
		if(is_array($metaData) && array_key_exists('exp_by_key', $metaData))
		{
			if($keyRoot !== '')
			{
				$keyPath = '/'.$this->processkey($keyRoot);
				$subsPath = $keyPath.'.subs';
				$subsPathLen = strlen($subsPath);
			}
			$expByKey = &$metaData['exp_by_key'];
			foreach($expByKey as $subPath => &$exp)
			{
				if
				(
					(
						$keyPath === null //All items targeted
						|| substr($subPath, 0, $subsPathLen) === $subsPath //item is within the subs of $keyRoot
						|| $keyPath === $subPath //item is $keyRoot
					)
					&&
					(
						$exp === 0 //Item has no expiry
						|| $exp >= $time //Item is not expired
					)
				)
				{
					//Renew the expiry time
					$exp = $expTime; 
					$dirty = true;
				}
			}
		}
//End critical section
		if($dirty)
		{
			$this->metaStore->writeAndUnlock($metaData);
		}
		else $this->metaStore->unlock();
	}

	protected function clearMetaSubPaths($expByKey, $keyPath, &$dirty)
	{
		$result=[];
		$pathLen=strlen($keyPath);
		foreach($expByKey as $subPath=>$exp)
		{
			//Discard key and any subkeys found in the meta data
			if(
				$subPath!==$keyPath &&
				substr($subPath,0,$pathLen+5)!==$keyPath.'.subs'
			)
			{
				$result[$subPath]=$exp;
			}
			else $dirty = true;
		}
		return $result;
	}

	protected function processKey($key)
	{
		$parts=null;
		$key=trim($key,'/');
		if(empty($key)) throw new Err('Invalid (empty) key.');
		$parts=explode('/',$key);
		foreach($parts as &$part) if(!ctype_alnum($part)) throw new Err('Invalid key: expected alphanumeric segments delimited by \'/\'');
		return implode('.subs/',$parts);
	}

	protected function start()
	{
		$fp=null;
		$metaData=null;
		$time=time();
		$this->gc=new \ZedBoot\Session\FileSession\GC($this->savePath,$this->expiry);
		if(!is_dir($this->savePath) && !mkdir($this->savePath,0700,true)) throw new Err('Unable to create directory '.$this->savePath);
		if(!ctype_alnum($this->sessionId)) throw new Err('Session id must be alphanumeric.');
		$this->dataPath=$this->savePath.'/'.$this->sessionId.'.data';
		//Renew the session
		$this->gc->initSession($this->sessionId,true);
		//Creation and deletion of data and subfolders happens while this file is locked
		$this->metaStore=new \ZedBoot\DataStore\FileDataStore($this->savePath.'/'.$this->sessionId.'.meta');
		//It is possible for the .meta file to be deleted while locking,
		//but it should have been renewed by $this->gc->initSession
//Begin critical section
		$metaData=$this->metaStore->lockAndRead();
		if(!is_array($metaData)) $metaData=[];
		$this->clearExpiredKeys($metaData,$time);
		if(!(is_dir($this->dataPath) || mkdir($this->dataPath,0700))) throw new Err('Unable to create data folder: '.$this->dataPath);
		$this->metaStore->writeAndUnlock($metaData);
//End critical section
		if($this->expiry>0 && $this->gcChance>0 && rand(0,$this->gcChance-1)==floor($this->gcChance/2)) $this->gc->gc();
		$this->started=true;
	}

	protected function clearExpiredKeys(&$metaData,$time)
	{
		if(empty($metaData['exp_by_key'])) $metaData['exp_by_key']=[];
		$expByKey=$metaData['exp_by_key'];
		$c=count($expByKey);
		//Clean up expired items
		//check one fifth-ish of keys, up to 10
		$c=$c>0?(min(floor($c/5+1),10)):0;
		$removedTree=[];
		for($i=0; $i<$c; $i++)
		{
			//get the first key off the queue
			reset($expByKey);
			$k=key($expByKey);
			$t=array_shift($expByKey);
			if($t!==0 && $t<$time) //Expiry=0 indicates no expiry
			{
				$p=$this->dataPath.'/'.$k;
				if(file_exists($p) && !unlink($p)) throw new Err('Could not remove data file '.$p);
				//Keep an index of paths that might now be empty
				$parts=explode('/',trim($k,'/'));
				//Don't include the file name - it is already gone
				array_pop($parts);
				$path=&$removedTree;
				if(count($parts)>0) foreach($parts as $part)
				{
					$dir=$part.'.sub';
					if(!array_key_exists($dir,$path)) $path[$dir]=[];
					$path=&$path[$dir];
				}
			}
			//key is ok - put it at the back of the queue
			else $expByKey[$k]=$t;
		}
		$this->clearEmptyPaths($this->savePath,$removedTree);
		$metaData['exp_by_key']=$expByKey;
	}
	
	protected function clearEmptyPaths($path,Array $pathTree)
	{
		$hasFiles=false;
		if(count($pathTree)>0)
		{
			//Clear child directories
			foreach($pathTree as $dir=>$subTree) $hasFiles=$hasFiles||$this->clearEmptyPaths($path.'/'.$dir,$subTree);
		}
		if(!$hasFiles) $hasFiles=count(glob($path.'/{,.}[!.,!..]*', GLOB_BRACE))>0;
		if(!$hasFiles) rmdir($path);
		return $hasFiles;
	}

	protected function getExpirableDataStore($subPath,$expiry,$forceCreate)
	{
		$subPath='/'.$subPath; //Numerical indices mess up clearExpiredKeys(), so make sure all indices are non-numerical
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
		//It is possible for the .meta file to be deleted while locking,
		//but it should have been renewed by start(),
		//so if the script has lasted long enough for it to expire there is a bigger problem
//Begin critical section
		$metaData=$this->metaStore->lockAndRead();
		if(!is_array($metaData)) $metaData=[];
		if(!array_key_exists('exp_by_key',$metaData)) $metaData['exp_by_key']=[];
		$expByKey=$metaData['exp_by_key'];
		if(array_key_exists($subPath,$expByKey))
		{
			//This key has been used - make sure it isn't expired
			//if it is expired clean up so the datastore will start fresh
			//Should be safe, since this critical section is the only place these file data stores are created,
			//and if has expired, whoever was using it last had better be done by now
			if($expByKey[$subPath]!==0 && $time>$expByKey[$subPath]) //Expiry===0 indicates no expiry
			{
				//DataStore has expired, clean up the file
				$p=$this->dataPath.$subPath;
				if(file_exists($p) && !unlink($p)) throw new Err('Could not remove data file '.$p);
			}
			//Datastore exists, so load it
			else $loadDS=true;
		}
		if($loadDS)
		{
			//Update expiry time, 0 for no expiry
			$expByKey[$subPath]=($expiry===0?0:$time+$expiry);
			$metaData['exp_by_key']=$expByKey;
			$this->metaStore->write($metaData);
			$result=new \ZedBoot\DataStore\FileDataStore($this->dataPath.$subPath);
		}
		else $result=null; //$forceCreate==false and datastore is nonexistent or expired
		$this->metaStore->unlock();
//End critical section
		return $result;
	}

	protected function rmdirRecursive($path)
	{
		$subs=null;
		if(empty($path) || !is_dir($path)) throw new Err('Attempt to remove non-existent directory '.$path);
		if(substr($path,0,strlen($this->savePath))!==$this->savePath) throw new Err('Attempt to remove directory not within the save path: '.$path.' (save path is '.$this->savePath.').');
		if(false===($subs=glob($path.'/{,.}[!.,!..]*', GLOB_BRACE))) throw new Err('Unable to get directory contents for '.$path);
		//Excluding . and .. is redundant, but a good idea for safety
		foreach($subs as $sub) if($sub!='.' && $sub!='..')
		{
			if(is_dir($sub))
			{
				$this->rmdirRecursive($sub);
			}
			else if(is_file($sub) && !unlink($sub)) throw new Err('Unable to delete file '.$sub);
		}
		if(!rmdir($path)) throw new Err('Unable to delete directory '.$path);
	}
}
