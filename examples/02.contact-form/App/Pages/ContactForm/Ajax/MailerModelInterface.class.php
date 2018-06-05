<?php
namespace ZedBoot\App\Pages\ContactForm\Ajax;
interface MailerModelInterface
{
	public function send($replyTo,$subject,$message);
	public function getStatus();
	public function getStatusMessage();
}
