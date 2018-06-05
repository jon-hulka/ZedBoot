<?php
namespace ZedBoot\App\Pages\ContactForm\Ajax;
class MailerModelTest implements MailerModelInterface
{
	protected
		$contactEmail=null,
		$status='unknown',
		$statusMessage='';
	public function __construct($contactEmail)
	{
		$this->contactEmail=$contactEmail;
	}
	public function send($replyTo,$subject,$message)
	{
		//Randomly select a result
		$statii=array(array('success','Message has been sent.'),array('fail','invalid email address.'),array('bot','Sorry, you appear to be a bot.'));
		$s=$statii[rand(0,2)];
		$this->status=$s[0];
		$this->statusMessage=$s[1];
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
