<?php
/**
 * Interface ErrorLoggerInterface | ZedBoot/System/Error/ErrorLoggerInterface.class.php
 * @license     GNU General Public License, version 3
 * @package     System
 * @subpackage  Error
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2016-2018 Jonathan Hulka
 */

/**
 * Error logger
 * Interface for convenient error logging and reporting.
 */
namespace ZedBoot\System\Error;
interface ErrorLoggerInterface
{
	/**
	 * If the error is to be logged, file and/or class, function (if available), and line number of calling script should be reported
	 * @param $userMessage String User friendly message
	 * @param $errorType mixed null if error is not to be logged, otherwise E_USER_NOTICE, E_USER_WARNING, or E_USER_ERROR, in case of invalid type, a warning should be issued and E_USER_NOTICE used
	 * @param $errorMessage mixed Error message to be used if $errorType is set, if null $userMessage will be used.
	 * @return boolean false for convenience: if($ok && !$do->something()) $ok=$this->errorHandler->setError($do->getError());
	 */
	public function setError($userMessage,$errorType=null,$errorMessage=null);
	public function clearError();
	public function getErrorClass();
	public function getErrorFunction();
	public function getErrorLineNumber();
	public function getErrorFile();
	public function getErrorMessage();
	/**
	 * @return String user friendly message
	 */
	public function getUserMessage();
	/**
	 * @return mixed null if no error is set, formatted error including class, function, line number and error message
	 */
	public function getError();
}
