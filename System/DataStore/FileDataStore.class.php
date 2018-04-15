<?php
/**
 * Class FileDataStore | ZedBoot/System/DataStore/FileDataStore.class.php
 * @license     GNU General Public License, version 3
 * @package     System
 * @subpackage  DataStore
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2016-2018 Jonathan Hulka
 */

/**
 * DataStoreInterface implementation
 * Data is saved to a file.
 */
namespace ZedBoot\System\DataStore;
class FileDataStore implements \ZedBoot\System\DataStore\DataStoreInterface
{
	protected
		$path=null,
		$filePointer=null,
		$errorLogger=null,
		$error=null;
	public function getError(){ return $this->error; }
	public function __construct($path, \ZedBoot\System\Error\ErrorLoggerInterface $errorLogger)
	{
		$this->path=$path;
		$this->errorLogger=$errorLogger;
	}
	public function lock()
	{
		$ok=true;
		$fp=null;
		if(is_null($this->filePointer))
		{
			if($ok)
			{
				$dir=dirname($this->path);
				if(!is_dir($dir) && !($ok=mkdir($dir,0700,true))) $this->errorLogger->setError($this->error='unable to create directory',\E_USER_WARNING,'unable to create directory: '.$this->path);
			}
			if($ok)
			{
				$fp=fopen($this->path,'c+',0600);
				if(!($ok=($fp!==false))) $this->errorLogger->setError($this->error='unable to open/create file',\E_USER_WARNING,'unable to open/create file: '.$this->path);
			}
			if($ok && !($ok=flock($fp,LOCK_EX))) $this->errorLogger->setError($this->error='lock failed',\E_USER_WARNING,'lock failed: '.$this->path);
			if($ok)
			{
				$this->filePointer=$fp;
			}
			else if($fp!==false) @fclose($fp); //Error has already been reported - this is a last-ditch effort to clean up
//This shouldn't be necessary
//			if($ok && !($ok=touch($this->path))) $this->errorLogger->setError($this->error='unable to update file modified time',\E_USER_WARNING,'touch failed: '.$this->path);
		}
		return $ok;
	}
	public function read(&$data)
	{
		$ok=true;
		$contents='';
		if($ok && !$ok=(!is_null($this->filePointer))) $this->errorLogger->setError($this->error='attempt to read without acquiring lock',\E_USER_WARNING);
		while ($ok && !feof($this->filePointer))
		{
			$chunk=fread($this->filePointer, 8192);
			if($chunk===false)
			{
				$ok=false;
				$this->errorLogger->setError($this->error='error reading',\E_USER_WARNING,'error reading: '.$this-path);
			}
			else $contents.=$chunk;
		}
		if($ok)
		{
			if(empty($contents))
			{
				$data=null;
			}
			else
			{
				$this->errorLogger->clearError();
				$this->error=null;
				$data=json_decode($contents,true);
				if(empty($data) && !($ok=(json_last_error()===JSON_ERROR_NONE))) $this->errorLogger->setError($this->error='decoding error',\E_USER_WARNING);
			}
		}
		return $ok;
	}
	public function write($data)
	{
		$ok=true;
		$output=null;
		if($ok && !$ok=(!is_null($this->filePointer))) $this->errorLogger->setError($this->error='attempt to write without acquiring lock',\E_USER_WARNING);
		if($ok)
		{
			$output=json_encode($data);
			if(!($ok=($output!==false))) $this->errorLogger->setError($this->error='encoding error',\E_USER_WARNING);
		}
		if($ok && !$ok=(rewind($this->filePointer))) $this->errorLogger->setError($this->error='error rewinding file',\E_USER_WARNING,'error rewinding file: '.$this->path);
		if($ok && !$ok=(ftruncate($this->filePointer,0))) $this->errorLogger->setError($this->error='error truncating file',\E_USER_WARNING,'error truncating file: '.$this->path);
		if($ok)
		{
			$written=0;
			$toWrite=strlen($output);
			while($toWrite>$written && $ok)
			{
				$w=fwrite($this->filePointer,substr($output,$written));
				if(!ok=($w!==false)) $this->errorLogger->setError($this->error='error writing file',\E_USER_WARNING,'error writing file: '.$this->path);
				else $written+=$w;
			}
		}
		return $ok;
	}
	public function unlock()
	{
		$ok=true;
		if(!is_null($this->filePointer)) //no effect if not locked
		{
			if($ok && !$ok=(flock($this->filePointer,LOCK_UN)))$this->errorLogger->setError($this->error='unlock failed',\E_USER_WARNING,'unlock failed: '.$this->path);
			if($ok)
			{
				fclose($this->filePointer);
				$this->filePointer=null;
			}
			if($ok && !($ok=touch($this->path))) $this->errorLogger->setError($this->error='unable to update file modified time',\E_USER_WARNING,'touch failed: '.$this->path);
		}
		return $ok;
	}
	public function quickRead(&$data)
	{
		$ok=true;
		$locked=false;
		if($ok) $ok=($locked=$this->lock());
		if($ok) $ok=$this->read($data);
		//Unlock is attempted even if an error has been encountered
		if($ok) { $ok=$this->unlock(); }
		else if($locked)
		{
			//Remember the first error as the unlock attempt will probably also fail
			$err=$this->error;
			$this->unlock();
			$this->error=$err;
		}
		return $ok;
	}
	public function quickWrite($data)
	{
		$ok=true;
		$locked=false;
		if($ok) $ok=($locked=$this->lock());
		if($ok) $ok=$this->write($data);
		if($ok) { $ok=$this->unlock(); }
		else if($locked)
		{
			//Remember the first error as the unlock attempt will probably also fail
			$err=$this->error;
			$this->unlock();
			$this->error=$err;
		}
		return $ok;
	}
}
