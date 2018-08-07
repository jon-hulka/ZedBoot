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
 *  5 uses route data to identify the request handler dependency
 *  6 loads the request handler dependency (this might be defined in a different configuration script, and loaded via dependency namespace (see 3.2.a)
 *  7 runs handleRequest and writeResponse on the request handler
 * B Dependencies
 *   At a minimum, these dependencies are available when the request handler is created:
 *  1 common:system.classLoader: \ZedBoot\System\Bootstrap\AutoLoader (ZedBoot namespace already configured)
 *    - Also available to dependency config scripts as $classLoader
 *  2 common:system.urlRouter: \ZedBoot\System\Bootstrap\URLRouter configured by the common dependencies config file (<base path>/ZedBoot/App/common.php)
 *  3 common:system.response: \ZedBoot\System\Bootstrap\ResponseInterface top level request handler configured by the dependency config file retrieved from route data
 *  4 common:system.basePath String path of directory containing ZedBoot and config directories
 *    - Also available to dependency config scripts as $basePath
 *  5 common:system.dependencyLoader \ZedBoot\System\DI\DependencyLoaderInterface
 *    - Also available to dependency config scripts as $dependencyLoader
 *  6 common:system.dependencyConfigLoader \ZedBoot\System\DI\DependencyConfigLoader
 *    - Also avaliable to dependency config scripts as $dependencyConfigLoader
 *  7 common:system.routeData as returned by system.urlRouter (C-2)
 *    - Also available to dependency config scripts (after URL routing) as $routeData
 *  8 common:system.baseURL as returned by system.urlRouter (C-2)
 *    - Also available to dependency config scripts (after URL routing) as $baseURL
 *  9 common:system.urlParts as returned by system.urlRouter (C-2)
 *    - Also available to dependency config scripts (after URL routing) as $urlParts
 *  10 common:system.urlParameters as returned by system.urlRouter (C-2)
 *    - Also available to dependency config scripts (after URL routing) as $urlParameters
 * C Dependencies Configuration
 *   In order for a successful page load, the common dependencies configuration script be set up:
 *  1 located at <base path>/ZedBoot/App/common.php
 *  2 system.urlRouter (\ZedBoot\System\Bootstrap\URLRouterInterface) must be defined in this config file
 *  3 each route data array must contain a 'response' element indicating the id of the response dependency
 * 		  - this element can (and should) be loaded via dependency namespace
 *          (ie an id of 'pages/test:response' will cause pages/test.php to be loaded into the dependency index before the dependency is resolved)
 *  4 each parameter is also available as a dependency. ie $basePath is also available as 'common:system.basePath', etc
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
	$dependenciesConfigFile=$dependenciesConfigPath.'/common.php';
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
		$configLoader=new \ZedBoot\System\DI\DependencyConfigLoader();
		$dependencyIndex=new \ZedBoot\System\DI\NamespacedDependencyIndex($configLoader, new \ZedBoot\System\DI\SimpleDependencyIndex(),$dependenciesConfigPath);
		$dependencyLoader=new \ZedBoot\System\DI\SimpleDependencyLoader($dependencyIndex);
		$configLoaderParameters['basePath']=$basePath;
		$configLoaderParameters['classLoader']=$loader;
		$configLoaderParameters['dependencyLoader']=$dependencyLoader;
		$configLoaderParameters['dependencyConfigLoader']=$dependencyLoader;
		
		$dependencyIndex->addParameters(array(
			'common:system.basePath'=>$basePath,
			'common:system.classLoader'=>$loader,
			'common:system.dependencyLoader'=>$dependencyLoader,
			'common:system.dependencyConfigLoader'=>$configLoader,
		));

		$configLoader->setConfigParameters($configLoaderParameters);
		//$configLoader->loadConfig is never explicitly called here
		//This allows all dependencies to be properly handled by NamespacedDependencyIndex
	
		//Get url router
		$router=$dependencyLoader->getDependency('common:system.urlRouter','\\ZedBoot\\System\\Bootstrap\\URLRouterInterface');

		//Resolve the route
		$url=explode('?',$_SERVER['REQUEST_URI'],2);
		$router->parseURL($url[0]);

		//Load route data
		$routeData=$router->getRouteData();
		$baseURL=$router->getBaseURL();
		$urlParts=$router->getURLParts();
		$urlParameters=$router->getURLParameters();
		if(!array_key_exists('response',$routeData)) throw new Err('response dependency not specified for route '.$baseURL);

		$configLoaderParameters['routeData']=$routeData;
		$configLoaderParameters['baseURL']=$baseURL;
		$configLoaderParameters['urlParts']=$urlParts;
		$configLoaderParameters['urlParameters']=$urlParameters;
		$dependencyIndex->addParameters(array(
			'common:system.routeData'=>$routeData,
			'common:system.baseURL'=>$baseURL,
			'common:system.urlParts'=>$urlParts,
			'common:system.urlParameters'=>$urlParameters,
		));
		$configLoader->setConfigParameters($configLoaderParameters);

		//Get the request handler
		$response=$dependencyLoader->getDependency($routeData['response'],'\\ZedBoot\\System\\Bootstrap\\ResponseInterface');
		
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
