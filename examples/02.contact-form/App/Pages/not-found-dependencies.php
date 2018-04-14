<?php
$services=array(
	'system.requestHandler'=>array(
		'\\ZedBoot\\App\\Pages\\NotFound',
		array('system.dependencyLoader','system.errorLogger'),
		true
	)
);
