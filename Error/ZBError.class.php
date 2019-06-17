<?php
namespace ZedBoot\Error;
class ZBError extends \Exception
{
	//May not be useful, since the init script will log all exceptions by default
	public static function createAndLog($message, $code = 0, Exception $previous = null)
	{
		$e=new ZBException($message, $code, $previous);
		error_log($e);
		return $e;
	}
}
