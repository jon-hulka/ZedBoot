<?php
namespace ZedBoot\App\Pages\NotFound;
class Response implements \ZedBoot\System\Bootstrap\ResponseInterface
{
	public function __construct()
	{}

	public function handleRequest()
	{
		//Controller stuff here
	}
	
	public function getHeaders()
	{
		return array(array('HTTP/1.0 404 Not Found', true, 404));
	}
	public function getResponseText()
	{
		return '<h1>Page not found.</h1>';
	}
}
