<?php
$pagesPath=$basePath.'/ZedBoot/App/Pages';
$notFound=array('dependencyConfig'=>$pagesPath.'/NotFound/dependencies.php');
$parameters=array(
//Home page view - no controller necessary
//Home page ajax:
// - handler
// - controller
// - models
// - view
	'system.routes'=>array(
		'*'=>$notFound,
		''=>array('dependencyConfig'=>$pagesPath.'/ContactForm/contact-form-dependencies.php'),
		'ajax/*'=>$notFound,
		'ajax/sendContactMessage'=>array('dependencyConfig'=>$pagesPath.'/ContactForm/Ajax/dependencies.php'),
	),
	'contact.email'=>'...'
);

$services=array(
	'system.urlRouter'=>array(
		'\\ZedBoot\\System\\Bootstrap\\SimpleURLRouter',
		array('system.routes'),
		true
	),
);

/*
$factoryServices=array(
//	 id=>array(factory id, factory function, optional arguments array, optional singleton boolean)
);
*/
