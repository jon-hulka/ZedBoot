<?php
namespace ZedBoot\App\Pages\ContactForm;
class MailerModelTest implements MailerModelInterface
{
	protected
		$errorLogger=null,
		$contactEmail=null,
		$status='unknown',
		$statusMessage='';
	public function getError()
	{
		return $this->errorLogger->getError();
	}
	public function __construct(\ZedBoot\System\Error\ErrorLoggerInterface $errorLogger, $contactEmail)
	{
		$this->contactEmail=$contactEmail;
		$this->errorLogger=$errorLogger;
	}
	public function send($replyTo,$subject,$message)
	{
		$ok=true;
		$statii=array(array('success','Message has been sent.'),array('fail','invalid email address.'),array('bot','Sorry, you appear to be a bot.'));
		$s=$statii[rand(0,2)];
		$this->status=$s[0];
		$this->statusMessage=$s[1];
		return $ok;
	}
	/**
	 * @return String 'success', 'fail', 'bot', 'unknown'
	 */
	public function getStatus()
	{
		return $this->status;
	}
	public function getStatusMessage()
	{
		return $this->statusMessage;
	}
}
