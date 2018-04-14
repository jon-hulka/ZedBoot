<?php
//See init.php for information on parameters available to this config script
//This configuration file defines dependencies common to the entire application
$parameters=array(
	//id=>value
	'system.dependencyLoader'=>$dependencyLoader,
	'system.routes'=>array(
		''=>array('dependencyConfig'=>$basePath.'/ZedBoot/App/home.php'), //home page configuration file
		'*'=>array('dependencyConfig'=>$basePath.'/ZedBoot/App/notFound.php'), //404 page configuration file
		//Additional routes will override the 404 route, since they are more specific
		//Typically if you don't want to allow url parameters, you should set up two routes:
		//'foo'=>array(...),
		//'foo/*'=>array(<404 config>),
		//Or to limit url parameters:
		//'foo'=>array(..), //'foo/bar' etc will resolve to this one
		//'foo/*/*'=>array(<404 config>), //'foo/bar/baz' etc will resolve to this one
	)
);

$services=array(
	//id=>array(class,array(...parameters by id (arrays can be nested)...),bool singleton)
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
