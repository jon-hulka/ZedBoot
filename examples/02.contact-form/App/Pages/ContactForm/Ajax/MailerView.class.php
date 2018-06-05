<?php
namespace ZedBoot\App\Pages\ContactForm\Ajax;
class MailerView implements \ZedBoot\App\ViewInterface
{	protected
		$model=null,
		$status=null;
	public function __construct(\ZedBoot\App\Pages\ContactForm\Ajax\MailerModelInterface $model)
	{
		$this->model=$model;
	}
	public function init()
	{
		$this->status=$this->model->getStatus();
		$this->message=$this->model->getStatusMessage();
	}
	public function getOutput()
	{
		return json_encode(array('status'=>$this->status,'message'=>nl2br(htmlspecialchars($this->message))));
	}
}
