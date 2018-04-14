<?php
namespace ZedBoot\App\RequestHandlers;
class NotFound implements \ZedBoot\System\Bootstrap\RequestHandlerInterface
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
		return true;
	}

	public function writeResponse()
	{
		http_response_code(404);
		echo '<h1>Page not found.</h1>';
	}
}
