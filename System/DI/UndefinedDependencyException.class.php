<?php
namespace ZedBoot\System\DI;
class UndefinedDependencyException extends \Exception
{
	protected
		$dependencyId=null;
	public function __construct($message, $dependencyId, $code=0, \Exception $previous=null)
	{
		$this->dependencyId=$dependencyId;
		parent::__construct($message,$code,$previous);
	}
	public function getDependencyId(){ return $this->dependencyId; }
}
