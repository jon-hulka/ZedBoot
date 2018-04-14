<?php
namespace ZedBoot\App\Pages\ContactForm;
interface MailerModelInterface extends \ZedBoot\System\Error\ErrorReporterInterface
{
	/**
	 * @return boolean error status
	 */
	public function send($replyTo,$subject,$message);
	public function getStatus();
	public function getStatusMessage();
}
