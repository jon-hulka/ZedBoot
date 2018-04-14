<?php
$notFound=array('dependencyConfig'=>$basePath.'/ZedBoot/App/Pages/not-found-dependencies.php');
$parameters=array(
//Home page view - no controller necessary
//Home page ajax:
// - handler
// - controller
// - models
// - view
	'system.dependencyLoader'=>$dependencyLoader,
	'system.routes'=>array(
		'*'=>$notFound,
		''=>array('dependencyConfig'=>$basePath.'/ZedBoot/App/Pages/ContactForm/contact-form-dependencies.php'),
		'ajax/*'=>$notFound,
		'ajax/sendContactMessage'=>array('dependencyConfig'=>$basePath.'/ZedBoot/App/Pages/ContactForm/mailer-dependencies.php'),
	),
	'contact.email'=>'...'
);

$services=array(
	'system.urlRouter'=>array(
		'\\ZedBoot\\System\\Bootstrap\\SimpleURLRouter',
		array('system.routes'),
		true
	),
	'system.errorLogger'=>array(
		'\\ZedBoot\\System\\Error\\SimpleErrorLogger',
		array(),
		true
	)
);

/*
$factoryServices=array(
//	 id=>array(factory id, factory function, optional arguments array, optional singleton boolean)
);
*/
