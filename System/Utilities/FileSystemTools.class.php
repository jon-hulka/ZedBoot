<?php
namespace ZedBoot\System\Utilities;
class FileSystemTools implements \ZedBoot\System\Error\ErrorReporterInterface
{
	protected
		$safePath=null;
	public function getError(){ return $this->errorLogger->getUserMessage(); }
	public function __construct($safePath)
	{
		if(empty($safePath)) throw new \Exception('Empty safe path.');
		$this->safePath=$safePath;
	}
	/**
	 * Recursively navigates a directory and deletes subdirectories and files
	 */
	public function rmdirRecursive($path)
	{
		$result=false;
		try
		{
			$this->rmr($path);
			$result=true;
		}
		catch(\Exception $e)
		{
			error_log($e);
			$this->error=$e->getMessage();
		}
		return $result;
	}
	protected function rmr($path)
	{
		$subs=null;
		if(empty($path) || !is_dir($path)) throw new \Exception('Attempt to remove non-existent directory '.$path);
		if(substr($path,0,strlen($this->safePath))!==$this->safePath) throw new \Exception('Attempt to remove directory not within the safe path: '.$path.' (safe path is '.$this->safePath.').');
		if(false===($subs=glob($path.'/{,.}[!.,!..]*', GLOB_BRACE))) throw new \Exception('System error: Unable to get directory contents for '.$path);
		//Excluding . and .. is redundant, but a good idea for safety
		foreach($subs as $sub) if($sub!='.' && $sub!='..')
		{
			if(is_dir($sub))
			{
				$this->rmr($sub);
			}
			else if(is_file($sub) && !unlink($sub)) throw new \Exception('System error: Unable to delete file '.$sub);
		}
		if(!rmdir($path)) throw new \Exception('Unable to delete directory '.$path);
	}
}
