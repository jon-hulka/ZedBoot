<?php
namespace ZedBoot\System\Utilities;
use \ZedBoot\System\Error\ZBError as Err;
class FileSystemTools
{
	protected
		$safePath=null;
	public function __construct($safePath)
	{
		if(empty($safePath)) throw new Err('Empty safe path.');
		$this->safePath=$safePath;
	}
	/**
	 * Recursively navigates a directory and deletes subdirectories and files
	 */
	public function rmdirRecursive($path)
	{
		$subs=null;
		if(empty($path) || !is_dir($path)) throw new Err('Attempt to remove non-existent directory '.$path);
		if(substr($path,0,strlen($this->safePath))!==$this->safePath) throw new Err('Attempt to remove directory not within the safe path: '.$path.' (safe path is '.$this->safePath.').');
		if(false===($subs=glob($path.'/{,.}[!.,!..]*', GLOB_BRACE))) throw new Err('System error: Unable to get directory contents for '.$path);
		//Excluding . and .. is redundant, but a good idea for safety
		foreach($subs as $sub) if($sub!='.' && $sub!='..')
		{
			if(is_dir($sub))
			{
				$this->rmr($sub);
			}
			else if(is_file($sub) && !unlink($sub)) throw new Err('System error: Unable to delete file '.$sub);
		}
		if(!rmdir($path)) throw new Err('Unable to delete directory '.$path);
	}
}
