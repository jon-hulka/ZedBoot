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
 *  3 system.requestHandler: \ZedBoot\System\Bootstrap\RequestHandlerInterface top level request handler configured by the dependency config file retrieved from route data
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
 *    b system.requestHandler (\ZedBoot\System\Bootstrap\RequestHandlerInterface) must be defined in this config file
 *    c each parameter is also available as a dependency: 'system.baseURL', etc
 */
function zbInit()
{
	$ok=true;
	$baseURL=null;
	$urlParts=null;
	$urlParameters=null;
	$basePath=dirname(dirname(__FILE__));
	$dependenciesConfigPath=$basePath.'/ZedBoot/App/common-dependencies.php';
	$router=null;
	$routeData=null;
	$requestHandler=null;
	$configLoaderParameters=array();

	try
	{
		//Set up the ZedBoot namespace
		require_once $basePath.'/ZedBoot/System/Bootstrap/AutoLoader.class.php';
		$loader=new \ZedBoot\System\Bootstrap\Autoloader();
		$loader->register('ZedBoot',$basePath.'/ZedBoot');

		//Set up shared dependencies
		$dependencyLoader=new \ZedBoot\System\DI\SimpleDependencyLoader();
		$configLoader=new \ZedBoot\System\DI\DependencyConfigLoader($dependencyLoader);
		$configLoaderParameters['basePath']=$basePath;
		$configLoaderParameters['classLoader']=$loader;
		$configLoaderParameters['dependencyLoader']=$dependencyLoader;
		$configLoaderParameters['dependencyConfigLoader']=$configLoader;
		
		$dependencyLoader->addParameters(array(
			'system.basePath'=>$basePath,
			'system.classLoader'=>$loader,
			'system.dependencyLoader'=>$dependencyLoader,
			'system.dependencyConfigLoader'=>$configLoader,
		));

		if(!$configLoader->loadConfig($dependenciesConfigPath,$configLoaderParameters))
			throw new \Exception('Could not load common dependency configuration: '.$configLoader->getError());
	
		//Get url router
		if(!$dependencyLoader->getDependency('system.urlRouter',$router,'\\ZedBoot\\System\\Bootstrap\\URLRouterInterface'))
			throw new \Exception('Could not load url router: '.$dependencyLoader->getError());

		//Resolve the route
		if(!$router->parseURL($_SERVER['REQUEST_URI']))
			throw new \Exception('Could not parse route: '.$router->getError());

		//Load route data
		$routeData=$router->getRouteData();
		$baseURL=$router->getBaseURL();
		$urlParts=$router->getURLParts();
		$urlParameters=$router->getURLParameters();
		if(!array_key_exists('dependencyConfig',$routeData)) throw new \Exception('Dependency configuration file not specified for route '.$baseURL);

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
		if(!$configLoader->loadConfig($routeData['dependencyConfig'],$configLoaderParameters))
			throw new \Exception('Could not load route dependency configuration: '.$configLoader->getError());

		//Get the request handler
		if(!$dependencyLoader->getDependency('system.requestHandler',$requestHandler,'\\ZedBoot\\System\\Bootstrap\\RequestHandlerInterface'))
			throw new \Exception('Could not load request handler: '.$dependencyLoader->getError());
		
		//Handle the request
		ob_start();
		if(!$requestHandler->handleRequest()) throw new \Exception('Could not handle request: '.$requestHandler->getError());
		ob_end_clean();

		$requestHandler->writeResponse();
	}
	catch(\Exception $e)
	{
		error_log($e);
		header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
		echo '<h1>Server error.</h1>';
	}
}
zbInit();
