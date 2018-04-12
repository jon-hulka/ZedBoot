<?php
//polyfill
//require_once dirname(dirname(__FILE__)).'/random_compat/lib/random.php';
//require_once dirname(dirname(__FILE__)).'/password_compat/lib/password.php';
/**
 * A Init Procedure
 *  1 prepares the classloader for the \ZedBoot namespace
 *  2 sets up the dependency loader
 *  3 initializes dependency loader for common dependencies
 *  4 uses the url router from common dependencies to load route data
 *  5 uses route data to identify the route dependency configuration file
 *  6 initializes the dependency loader for route dependencies
 *  7 loads the request handler from route dependencies
 *  8 runs handleRequest and writeResponse on the request handler
 * B Dependencies
 *   At a minimum, these dependencies are available when the request handler is invoked:
 *  1 system.classLoader: \ZedBoot\System\Bootstrap\Autoloader
 *  2 system.urlRouter: \ZedBoot\System\Bootstrap\URLRouter configured by the common dependencies config file (typically <base path>/config/common-dependencies.php)
 *  3 system.requestHandler: \ZedBoot\System\Bootstrap\RequestHandlerInterface top level request handler configured by the dependency config file retrieved from route data
 * C Dependencies Configuration
 *   In order for a successful page load, these must be set up:
 *  1 common dependencies script
 *    a <base path>/config/common-dependencies.php
 *    b the following parameters are available to the common dependencies config script:
 *      i   $basePath String path of directory containing ZedBoot and config directories
 *      ii  $classLoader \ZedBoot\System\Bootstrap\AutoLoader (ZedBoot namespace already configured)
 *      iii $dependencyLoader \ZedBoot\System\DI\DependencyLoaderInterface
 *      iv  $dependencyConfigLoader \ZedBoot\System\DI\DependencyConfigLoader
 *    c system.urlRouter (\ZedBoot\System\Bootstrap\URLRouterInterface) must be defined in this config file
 *      i   each route data array must contain a 'dependencyConfig' element indicating the path of the route's dependency configuration file
 *  2 route dependency script for each route
 *    To be loaded into dependency loader once retrieved from url router (C-1-c-i)
 *    a the following parameters are available to the route dependencies config script:
 *      i    $basePath String path of directory containing ZedBoot and config directories
 *      ii   $classLoader \ZedBoot\System\Bootstrap\AutoLoader (ZedBoot namespace already configured)
 *      iii  $dependencyLoader \ZedBoot\System\DI\DependencyLoaderInterface
 *      iv   $dependencyConfigLoader \ZedBoot\System\DI\DependencyConfigLoader
 *      v    $routeData as returned by system.urlRouter (C-1-c)
 *      vi   $baseURL as returned by system.urlRouter (C-1-c)
 *      vii  $urlParts as returned by system.urlRouter (C-1-c)
 *      viii $urlParameters as returned by system.urlRouter (C-1-c)
 *    b system.requestHandler (\ZedBoot\System\Bootstrap\RequestHandlerInterface) must be defined in this config file
 */
function zbInit()
{
	$ok=true;
	$baseURL=null;
	$urlParts=null;
	$urlParameters=null;
	$basePath=dirname(dirname(__FILE__));
	$dependenciesConfigPath=$basePath.'/config/common-dependencies.php';
	$router=null;
	$routeData=null;
	$requestHandler=null;
	$configLoaderParameters=array();

	//Set up the ZedBoot namespace
	require_once $basePath.'/ZedBoot/System/Bootstrap/AutoLoader.class.php';
	$loader=new \ZedBoot\System\Bootstrap\Autoloader();
	$loader->register('ZedBoot',$basePath.'/ZedBoot');

	//Set up shared dependencies
	$dependencyLoader=new \ZedBoot\System\DI\SimpleDependencyLoader();
	$configLoader=new \ZedBoot\System\DI\DependencyConfigLoader($dependencyLoader);
	
	if($ok)
	{
		$configLoaderParameters['basePath']=$basePath;
		$configLoaderParameters['classLoader']=$loader;
		$configLoaderParameters['dependencyLoader']=$dependencyLoader;
		$configLoaderParameters['dependencyConfigLoader']=$configLoader;

		if(!($ok=$dependencyLoader->addParameters(array('system.basePath'=>$basePath,'system.classLoader'=>$loader,'system.dependencyConfigLoader'=>$configLoader))))
			error_log(__FILE__.': could not add system parameters (first set): '.$dependencyLoader->getError());
	}

	if($ok && !($ok=$configLoader->loadConfig($dependenciesConfigPath,$configLoaderParameters)))
		error_log(__FILE__.': could not load common dependency configuration: '.$configLoader->getError());
	
	//Get url router
	if($ok && !($ok=$dependencyLoader->getDependency('system.urlRouter',$router,'\\ZedBoot\\System\\Bootstrap\\URLRouterInterface')))
		error_log(__FILE__.': could not load url router: '.$dependencyLoader->getError());

	//Resolve the route
	if($ok && !($ok=$router->parseURL($_SERVER['REQUEST_URI'])))
		error_log(__FILE__.': Could not parse route: '.$router->getError());
	
	//Load route data
	if($ok)
	{
		$routeData=$router->getRouteData();
		$baseURL=$router->getBaseURL();
		$urlParts=$router->getURLParts();
		$urlParameters=$router->getURLParameters();
		if(!array_key_exists('dependencyConfig',$routeData))
		{
			$ok=false;
			$error_log(__FILE__.': dependency configuration file not specified for route '.$baseURL);
		}
	}
	
	if($ok)
	{
		$configLoaderParameters['routeData']=$routeData;
		$configLoaderParameters['baseURL']=$baseURL;
		$configLoaderParameters['urlParts']=$urlParts;
		$configLoaderParameters['urlParameters']=$urlParameters;
		if(!($ok=$dependencyLoader->addParameters(array('system.routeData'=>$routeData, 'system.baseURL'=>$baseURL,'system.urlParts'=>$urlParts,'system.urlParameters'=>$urlParameters))))
			error_log(__FILE__.': could not add system parameters (second set): '.$dependencyLoader->getError());
	}

	//dependencyConfig should be one of the data items
	if($ok && !($ok=$configLoader->loadConfig($routeData['dependencyConfig'],$configLoaderParameters)))
		error_log(__FILE__.': could not load route dependency configuration: '.$configLoader->getError());
	
	//Get the request handler
	if($ok && !($ok=$dependencyLoader->getDependency('system.requestHandler',$requestHandler,'\\ZedBoot\\System\\Bootstrap\\RequestHandlerInterface')))
		error_log(__FILE__.': could not load request handler: '.$dependencyLoader->getError());
	
	//Handle the request
	ob_start();
	if($ok && !($ok=$requestHandler->handleRequest()))
		error_log(__FILE__.': could not handle request: '.$requestHandler->getError());
	ob_end_clean();
	
	if(!$ok)
	{
		header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
		echo '<h1>Server error.</h1>';
	}
	else $requestHandler->writeResponse();
}
zbInit();
