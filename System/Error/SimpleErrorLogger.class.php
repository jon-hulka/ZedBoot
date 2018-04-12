<?php
/**
 * Class SimpleErrorLogger | ZedBoot/System/Error/SimpleErrorLogger.class.php
 * @license     GNU General Public License, version 3
 * @package     System
 * @subpackage  Error
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2016-2018 Jonathan Hulka
 */

/**
 * Simple error logger
 * Errors are output to the default error log
 */
namespace ZedBoot\System\Error;
class SimpleErrorLogger implements \ZedBoot\System\Error\ErrorLoggerInterface
{
	protected
		$errorClass=null,
		$errorFunction=null,
		$errorLine=null,
		$errorFile=null,
		$userMessage=null,
		$errorMessage=null;
	public function setError($userMessage,$errorType=null,$errorMessage=null)
	{
		$bt=debug_backtrace(false,2);
		$this->clearError();
		$this->userMessage=empty($userMessage)?'Unknown Error':$userMessage;
		$this->errorMessage=is_null($errorMessage)?$this->userMessage:$errorMessage;
		if(!empty($bt[1]['class'])) $this->errorClass=$bt[1]['class'];
		if(!empty($bt[1]['function'])) $this->errorFunction=$bt[1]['function'];
		if(!empty($bt[0]['file'])) $this->errorFile=$bt[0]['file'];
		if(!empty($bt[0]['line'])) $this->errorLine=$bt[0]['line'];
//Uncomment this for debugging if the source of the error can't be determined otherwise
//error_log(get_class($this).'::'.__FUNCTION__.': '.json_encode($bt));
		if(!empty($errorType))
		{
			if($errorType!==\E_USER_NOTICE && $errorType!==\E_USER_WARNING && $errorType!==\E_USER_ERROR)
			{
				trigger_error($this->getErrorPrefix().'Invalid error type specified, defaulting to E_USER_NOTICE',\E_USER_WARNING);
				$errorType=\E_USER_NOTICE;
			}
			trigger_error($this->getError(),$errorType);
		}
		return false;
	}
	public function clearError()
	{
		$this->errorClass=null;
		$this->errorFunction=null;
		$this->errorLine=null;
		$this->errorFile=null;
		$this->userMessage=null;
		$this->errorMessage=null;
	}
	public function getErrorFile(){ return $this->errorFile; }
	public function getErrorClass(){ return $this->errorClass; }
	public function getErrorFunction(){ return $this->errorFunction; }
	public function getErrorLineNumber(){ return $this->errorLine; }
	public function getErrorMessage(){ return $this->errorMessage; }
	public function getUserMessage(){ return $this->userMessage; }
	protected function getErrorPrefix()
	{
		$result='';
		if(!empty($this->errorClass)) $result.=$this->errorClass.'::';
		if(empty($result) && !empty($this->errorFile)) $result.=$this->errorFile.' ';
		if(!empty($this->errorFunction)) $result.=$this->errorFunction.'() ';
		if(!empty($this->errorLine)) $result.='(line '.$this->errorLine.'): ';
		return $result;
	}
	public function getError()
	{
		return empty($this->errorMessage)?null:$this->getErrorPrefix().$this->errorMessage;
	}
}
