<?php
//polyfill
//require_once dirname(dirname(__FILE__)).'/random_compat/lib/random.php';
//require_once dirname(dirname(__FILE__)).'/password_compat/lib/password.php';
/**
 * A Init Procedure
 *  1 prepares the classloader for the \ZedBoot namespace
 *  2 sets up the dependency loader
 *  3 adds common dependencies to the dependency loader
 *  4 uses the url router from common dependencies to load route data
 *  5 uses route data to identify the route dependency configuration file
 *  6 adds route dependencies to the dependency loader
 *  7 loads the request handler from route dependencies
 *  8 runs handleRequest and writeResponse on the request handler
 * B Dependencies
 *   At a minimum, these dependencies are available when the request handler is created:
 *  1 system.classLoader: \ZedBoot\System\Bootstrap\AutoLoader (ZedBoot namespace already configured)
 *    - Also available to both scripts as $classLoader
 *  2 system.urlRouter: \ZedBoot\System\Bootstrap\URLRouter configured by the common dependencies config file (typically <base path>/ZedBoot/App/common-dependencies.php)
 *  3 system.response: \ZedBoot\System\Bootstrap\ResponseInterface top level request handler configured by the dependency config file retrieved from route data
 *  4 system.basePath String path of directory containing ZedBoot and config directories
 *    - Also available to both scripts as $basePath
 *  5 system.dependencyLoader \ZedBoot\System\DI\DependencyLoaderInterface
 *    - Also available to both scripts as $dependencyLoader
 *  6 system.dependencyConfigLoader \ZedBoot\System\DI\DependencyConfigLoader
 *    - Also avaliable to both scripts as $dependencyConfigLoader
 *  7 system.routeData as returned by system.urlRouter (C-1-b)
 *    - Also available to the route script as $routeData
 *  8 system.baseURL as returned by system.urlRouter (C-1-b)
 *    - Also available to the route script as $baseURL
 *  9 system.urlParts as returned by system.urlRouter (C-1-b)
 *    - Also available to the route script as $urlParts
 *  10 system.urlParameters as returned by system.urlRouter (C-1-b)
 *    - Also available to the route script as $urlParameters
 * C Dependencies Configuration
 *   In order for a successful page load, these must be set up:
 *  1 common dependencies script
 *    a <base path>/ZedBoot/App/common-dependencies.php
 *    b system.urlRouter (\ZedBoot\System\Bootstrap\URLRouterInterface) must be defined in this config file
 *      i   each route data array must contain a 'dependencyConfig' element indicating the path of the route's dependency configuration file
 *    c each parameter is also available as a dependency. ie $basePath is also available as 'system.basePath', etc
 *  2 route dependency script for each route
 *    To be loaded into dependency loader once retrieved from url router (C-1-b-i)
 *    b system.response (\ZedBoot\System\Bootstrap\ResponseInterface) must be defined in this config file
 *    c each parameter is also available as a dependency: 'system.baseURL', etc
 */
use \ZedBoot\System\Error\ZBError as Err;
function zbInit()
{
	$ok=true;
	$baseURL=null;
	$urlParts=null;
	$urlParameters=null;
	$basePath=dirname(dirname(__FILE__));
	$dependenciesConfigPath=$basePath.'/ZedBoot/App/DI';
	$dependenciesConfigFile=$dependenciesConfigPath.'/common-dependencies.php';
	$router=null;
	$routeData=null;
	$response=null;
	$output=null;
	$configLoaderParameters=array();
	$obStarted=false;
	try
	{
		//Set up the ZedBoot namespace
		require_once $basePath.'/ZedBoot/System/Bootstrap/AutoLoader.class.php';
		$loader=new \ZedBoot\System\Bootstrap\Autoloader();
		$loader->register('ZedBoot',$basePath.'/ZedBoot');

		//Set up shared dependencies
		//$dependencyLoader is a DependencyLoaderInterface and a DependencyConfigLoader
		$dependencyLoader=new \ZedBoot\System\DI\NamespacedDependencyLoader(new \ZedBoot\System\DI\SimpleDependencyLoader(),$dependenciesConfigPath);
		$configLoaderParameters['basePath']=$basePath;
		$configLoaderParameters['classLoader']=$loader;
		$configLoaderParameters['dependencyLoader']=$dependencyLoader;
		$configLoaderParameters['dependencyConfigLoader']=$dependencyLoader;
		
		$dependencyLoader->addParameters(array(
			'system.basePath'=>$basePath,
			'system.classLoader'=>$loader,
			'system.dependencyLoader'=>$dependencyLoader,
			'system.dependencyConfigLoader'=>$dependencyLoader,
		));

		$dependencyLoader->setConfigParameters($configLoaderParameters);
		$dependencyLoader->loadConfig($dependenciesConfigFile);
	
		//Get url router
		$router=$dependencyLoader->getDependency('system.urlRouter','\\ZedBoot\\System\\Bootstrap\\URLRouterInterface');

		//Resolve the route
		$url=explode('?',$_SERVER['REQUEST_URI'],2);
		$router->parseURL($url[0]);

		//Load route data
		$routeData=$router->getRouteData();
		$baseURL=$router->getBaseURL();
		$urlParts=$router->getURLParts();
		$urlParameters=$router->getURLParameters();
		if(!array_key_exists('dependencyConfig',$routeData)) throw new Err('Dependency configuration file not specified for route '.$baseURL);

		$configLoaderParameters['routeData']=$routeData;
		$configLoaderParameters['baseURL']=$baseURL;
		$configLoaderParameters['urlParts']=$urlParts;
		$configLoaderParameters['urlParameters']=$urlParameters;
		$dependencyLoader->addParameters(array(
			'system.routeData'=>$routeData,
			'system.baseURL'=>$baseURL,
			'system.urlParts'=>$urlParts,
			'system.urlParameters'=>$urlParameters,
		));
		$dependencyLoader->setConfigParameters($configLoaderParameters);
		$dependencyLoader->loadConfig($routeData['dependencyConfig']);

		//Get the request handler
		$response=$dependencyLoader->getDependency('system.response','\\ZedBoot\\System\\Bootstrap\\ResponseInterface');
		
		//Handle the request
		ob_start(); $obStarted=true; //$response shouldn't write anything to output - if it does, ignore.
		$response->handleRequest();
		$output=$response->getResponseText();
		$headers=$response->getHeaders();
		if(!is_array($headers)) throw new Err('Expected array from '.get_class($response).'::getHeaders(), got '.getType($headers).'.');
		foreach($headers as $header)
		{
			//Bad headers will be handled before any are sent. If any are bad, none will be sent
			if(!is_array($header)) throw new Err('Invalid header in list returned by '.get_class($response).'::getHeaders(): expected array, got '.getType($header).'.');
			if(count($header)<1) throw new Err('invalid header in list returned by '.get_class($response).'::getHeaders(): Each header must have at least one parameter.');
		}
		foreach($headers as $header)
		{
			$h=array_shift($header);
			$r=true;
			$c=null;
			if(count($header)>0)$r=array_shift($header);
			if(count($header)>0)$c=array_shift($header);
			if($c===null){ header($h,$r); } else header($h,$r,$c);
		}
		ob_end_clean(); $obStarted=false;
		echo $output;
	}
	catch(\Exception $e)
	{
		if($obStarted) ob_end_clean();
		error_log($e);
		header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
		echo '<h1>Server error.</h1>';
	}
}
zbInit();
