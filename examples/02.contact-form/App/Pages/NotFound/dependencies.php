<?php
//See init.php for information on parameters available to this config script
//This configuration file defines dependencies specific to the page
//See common dependencies for more information on setting up a dependecies configuration
$services=array(
	'system.response'=>array(
		'\\ZedBoot\\App\\Pages\\NotFound\\Response',
		array(),
		true
	)
);
