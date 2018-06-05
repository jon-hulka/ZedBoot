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
use \ZedBoot\System\Error\ZBError as Err;
class FileDataStore implements \ZedBoot\System\DataStore\DataStoreInterface
{
	protected
		$path=null,
		$filePointer=null;
	public function __construct($path)
	{
		$this->path=$path;
	}
	public function lockAndRead(){ $this->lock(); return $this->read(); }
	public function writeAndUnlock($data){ $this->write($data); $this->unlock(); }
	public function lock()
	{
		$fp=null;
		if(is_null($this->filePointer))
		{
			$dir=dirname($this->path);
			if(!is_dir($dir) && !mkdir($dir,0700,true)) throw new Err('Unable to create directory '.$dir.'.');
			if(false===($fp=fopen($this->path,'c+',0600))) throw new Err('Unable to open/create file '.$this->path.'.');
			if(!flock($fp,LOCK_EX)) throw new Err('Lock failed: '.$this->path);
			$this->filePointer=$fp;
		}
	}
	public function read()
	{
		$result=null;
		$contents='';
		if(is_null($this->filePointer)) throw new Err('Attempt to read without acquiring lock.');
		while (!feof($this->filePointer))
		{
			if(false===($chunk=fread($this->filePointer, 8192))) throw new Err('Reading '.$this->path.'.');
			$contents.=$chunk;
		}
		if(empty($contents))
		{
			$data=null;
		}
		else
		{
			$result=json_decode($contents,true);
			if(empty($result) && json_last_error()!==JSON_ERROR_NONE) throw new Err('Decoding JSON.');
		}
		return $result;
	}
	public function write($data)
	{
		$output=null;
		if(is_null($this->filePointer)) throw new Err('Attempt to write without acquiring lock.');
		$output=json_encode($data);
		if($output===false) throw new Err('System error: encoding JSON.');
		if(!rewind($this->filePointer)) throw new Err('System eror: rewinding file '.$this->path);
		if(!ftruncate($this->filePointer,0)) throw new Err('System error: truncating file '.$this->path);
		$written=0;
		$toWrite=strlen($output);
		while($toWrite>$written)
		{
			if(false===($w=fwrite($this->filePointer,substr($output,$written)))) throw new Err('System error writing '.$this->path);
			$written+=$w;
		}
	}
	public function unlock()
	{
		if(!is_null($this->filePointer)) //no effect if not locked
		{
			if(!flock($this->filePointer,LOCK_UN)) throw new \Err('System error: unlock failed: '.$this->path);
			if(!fclose($this->filePointer)) throw new \Exception('System error: close file failed: '.$this->path);
			$this->filePointer=null;
			if(!touch($this->path)) throw new \Exception('System error: touch failed: '.$this->path);
		}
	}
	public function quickRead()
	{
		$this->lock();
		$result=$this->read();
		$this->unlock();
		return $result;
	}
	public function quickWrite($data)
	{
		$this->lock();
		$this->write($data);
		$this->unlock();
	}
}
