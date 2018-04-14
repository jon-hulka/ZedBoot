<?php
namespace ZedBoot\App\RequestHandlers;
class Home implements \ZedBoot\System\Bootstrap\RequestHandlerInterface
{
	protected
		$errorLogger=null;
	public function __construct(
		\ZedBoot\System\DI\DependencyLoaderInterface $dependencyLoader,
		\ZedBoot\System\Error\ErrorLoggerInterface $errorLogger)
	{
		$this->dependencyLoader=$dependencyLoader;
		$this->errorLogger=$errorLogger;
	}

	public function getError(){ return $this->errorLogger->getError(); }

	public function handleRequest()
	{
		//Controller stuff here
		return true;
	}

	public function writeResponse()
	{
		//View stuff here
		echo '<h1>Welcome home!</h1>';
	}
}
