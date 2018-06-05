<?php
namespace ZedBoot\App\Pages\ContactForm;
class Response implements \ZedBoot\System\Bootstrap\ResponseInterface
{
	public function __construct()
	{
	}

	public function handleRequest()
	{
	}
	
	public function getHeaders()
	{
		return array();
	}

	public function getResponseText()
	{
		//For more complex pages, this should be delegated to a view
		//In this case, it is the only thing this class does
		ob_start();
		try
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
		catch(\Exception $e)
		{
			ob_end_clean();
			throw $e;
		}
		return ob_get_clean();
	}
}
