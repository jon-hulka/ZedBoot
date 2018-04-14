<?php
namespace ZedBoot\App\Pages;
class AjaxHandler implements \ZedBoot\System\Bootstrap\RequestHandlerInterface
{
	protected
		$errorLogger=null,
		$view=null;
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
		$ok=true;
		$controller=null;
		if($ok && !($ok=$this->dependencyLoader->getDependency('ajax.controller',$controller,'\\ZedBoot\\App\\ControllerInterface')))
			$this->errorLogger->setError('Could not load controller.',\E_USER_ERROR,$this->dependencyLoader->getError());
		if($ok && !($ok=$controller->update()))
			$this->errorLogger->setError('Controller::update() failed.',\E_USER_ERROR,$controller->getError());
		if($ok && !($ok=$this->dependencyLoader->getDependency('ajax.view',$this->view,'\\ZedBoot\\App\\ViewInterface')))
			$this->errorLogger->setError('Could not load view.',\E_USER_ERROR,$this->dependencyLoader->getError());
		if($ok && !($ok=$this->view->init()))
			$this->errorLogger->setError('View::init() failed.',\E_USER_ERROR,$this->view->getError());
		return $ok;
	}

	public function writeResponse()
	{
		if(!empty($this->view)) $this->view->output();
	}
}
