<?php
namespace ZedBoot\App\Pages\ContactForm;
class ContactForm implements \ZedBoot\System\Bootstrap\RequestHandlerInterface
{
	protected
		$errorLogger=null;
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
		return true;
	}

	public function writeResponse()
	{
?>
<html>
	<head>
		<title>Contact form</title>
	</head>
	<body>
		<h2 id="response"></h2>
		<div id="contact-form">
			<label for="email">Email Address</label><br />
			<input id="email" name="email" type="text" /><br />
			<label for="subject">Subject</label><br />
			<input id="subject" name="subject" type="text" /><br />
			<label for="message">Message</label><br />
			<textarea id="message" name="message"></textarea><br />
			<button id="contact-send">Send</button>
		</div>
	</body>
	<script src="/js/jquery.min.js"></script>
	<script>
$(function(){
	$('#contact-send').on('click',function(){
		$.ajax({
			url: "/ajax/sendContactMessage",
			data: $('#contact-form').serialize(),
			dataType: 'json'
		})
		.done(function( data ) {
			var resp='Unknown error.';
			if('message' in data) resp=data.message;
			$('#response').html(resp);
			if('status' in data && data.status=='success')
			{
				$('#email').val('');
				$('#subject').val('');
				$('#message').val('');
			}
		});

	});
});
	</script>
</html>
<?php
	}
}
