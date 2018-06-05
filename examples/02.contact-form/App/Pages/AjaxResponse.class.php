<?php
namespace ZedBoot\App\Pages;
class AjaxResponse implements \ZedBoot\System\Bootstrap\ResponseInterface
{
	protected
		$controller=null,
		$view=null;
	public function __construct(\ZedBoot\App\ControllerInterface $controller, \ZedBoot\App\ViewInterface $view)
	{
		$this->controller=$controller;
		$this->view=$view;
	}

	public function handleRequest()
	{
		$this->controller->update();
		$this->view->init();
	}
	
	public function getHeaders()
	{
		return array();
	}

	public function getResponseText()
	{
		return $this->view->getOutput();
	}
}
