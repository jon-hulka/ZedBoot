<?php
namespace ZedBoot\Session\FileSession;
use \ZedBoot\Error\ZBError as Err;
class GC
{
	protected
		$savePath,
		$expiry,
		$lockFP=null,
		$started=false;
	public function __construct($savePath,$expiry)
	{
		$this->savePath=$savePath;
		$this->expiry=$expiry;
	}
	
	/**
	 * Cleans up session if expired, renews otherwise
	 */
	public function initSession($sessionId)
	{
		$time=time();
		$metaPath=$this->savePath.'/'.$sessionId.'.meta';
		if(!$this->started) $this->start();
		if(!flock($this->lockFP,LOCK_EX)) throw new Err('Lock failed.');
		if(file_exists($metaPath))
		{
			$mtime=filemtime($metaPath);
			if($time-$mtime<=$this->expiry)
			{
				//Not past expiry yet
				touch($metaPath);
			}
			else $this->clearSession($sessionId);
		}
		if(!flock($this->lockFP,LOCK_UN)) throw new Err('Unlock failed.');
	}
	
	/**
	 * Run garbage collection
	 */
	public function gc()
	{
		$mt=null;
		$time=time();
		$toCheck=[];
		if(!$this->started) $this->start();
		if(!flock($this->lockFP,LOCK_EX)) throw new Err('Lock failed.');
		//All sessions are marked with a .meta file
		if(false===($files=glob($this->savePath.'/*.meta'))) throw new Err('Unable to search session directory: glob('.$this->savePath.'/*.meta) failed.');
		foreach($files as $file)
		{
			//This is just a quick check to speed things up. A more comprehensive lock and check is made in gcProcessSession()
			if(false===($mt=filemtime($file))) throw new Err('Unable to get modified time for meta file: filemtime('.$this->dataPath.'/'.$file.') failed');
			if($time-$mt>$this->expiry) $toCheck[]=basename($file,'.meta');
		}
		foreach($toCheck as $id) $this->clearSession($id);
		if(!flock($this->lockFP,LOCK_UN)) throw new Err('Unlock failed.');
	}
	
	protected function start()
	{
		//In order to safely lock meta datastores and ensure no race conditions with gc, everything synchronizes on this file
		if(false===($this->lockFP=fopen($this->savePath.'/.lock','c+'))) throw new Err('Unable to open/create file '.$this->savePath.'/.lock');
		$this->started=true;
	}
	
	/**
	 * Must happen within critical section to prevent race conditions
	 */
	protected function clearSession($sessionId)
	{
		$mtime=null;
		$metaPath=$this->savePath.'/'.$sessionId.'.meta';
		$dataPath=$this->savePath.'/'.$sessionId.'.data';
		//if file hasn't been modified, recursively remove .data directory
		if(is_dir($dataPath)) $this->rmdirRecursive($dataPath);
		if(!unlink($metaPath)) throw new Err('Unable to remove meta file '.$metaPath);
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
