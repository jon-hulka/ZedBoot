<?php
namespace ZedBoot\App\Pages\ContactForm;
class MailerController implements \ZedBoot\App\ControllerInterface
{
	protected
		$model=null,
		$errorLogger=null;
	public function __construct(\ZedBoot\System\Error\ErrorLoggerInterface $errorLogger, \ZedBoot\App\Pages\ContactForm\MailerModelInterface $model)
	{
		$this->errorLogger=$errorLogger;
		$this->model=$model;
	}
	public function getError()
	{
		return $this->errorLogger->getError();
	}
	public function update()
	{
		$ok=true;
		$replyTo=empty($_POST['reply_to'])?null:$_POST['reply_to'];
		$subject=empty($_POST['subject'])?null:$_POST['subject'];
		$message=empty($_POST['message'])?null:$_POST['message'];
		if(!$ok=$this->model->send($replyTo,$subject,$message)) $this->errorLogger->setError('Failed to send.',\E_USER_ERROR,$this->model->getError());
		return $ok;
	}
}
