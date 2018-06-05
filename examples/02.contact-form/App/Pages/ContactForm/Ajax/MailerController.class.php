<?php
namespace ZedBoot\App\Pages\ContactForm\Ajax;
class MailerController implements \ZedBoot\App\ControllerInterface
{
	protected
		$model=null;
	public function __construct(\ZedBoot\App\Pages\ContactForm\Ajax\MailerModelInterface $model)
	{
		$this->model=$model;
	}
	public function update()
	{
		$replyTo=empty($_POST['reply_to'])?null:$_POST['reply_to'];
		$subject=empty($_POST['subject'])?null:$_POST['subject'];
		$message=empty($_POST['message'])?null:$_POST['message'];
		$this->model->send($replyTo,$subject,$message);
	}
}
