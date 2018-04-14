<?php
$services=array(
	'system.requestHandler'=>array(
		'\\ZedBoot\\App\\Pages\\ContactForm\\ContactForm',
		array('system.dependencyLoader','system.errorLogger'),
		true
	)
);
