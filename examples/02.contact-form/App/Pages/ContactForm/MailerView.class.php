<?php
namespace ZedBoot\App\Pages\ContactForm;
class MailerView implements \ZedBoot\App\ViewInterface
{	protected
		$model=null,
		$errorLogger=null,
		$status=null,
		$message=null;
	public function __construct(\ZedBoot\System\Error\ErrorLoggerInterface $errorLogger, \ZedBoot\App\Pages\ContactForm\MailerModelInterface $model)
	{
		$this->errorLogger=$errorLogger;
		$this->model=$model;
	}
	public function getError()
	{
		return $this->errorLogger->getError();
	}
	public function init()
	{
		$ok=true;
		$this->status=$this->model->getStatus();
		$this->message=$this->model->getStatusMessage();
		return $ok;
	}
	public function output()
	{
		echo json_encode(array('status'=>$this->status,'message'=>nl2br(htmlspecialchars($this->message))));
	}
}
