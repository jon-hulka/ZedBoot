<?php
/* There should be dependency configuration file named 'boot.php' in the path specified by $settingsDir
 * It is expected to have the following:
 * in $parameters:
 *   'classRegistry' =>
 *   [
 *      [
 *        'path'=> <string, required>, 
 *        'namespace' => <string, required>,
 *        `suffix' => <string (filename suffix), optonal, defaults to '.class.php'>,
 *        'prefix' => <string (filename prefix), optonal, defaults to ''>
 *      ],
 *      ...
 *  ],
 *  'sharedParameters => 
 *  [
 *    //Anything to be shared with other config files (more information below)   
 *  ]
 * in $services:
 *   - service definition for urlRouter, implementing \ZedBoot\System\Bootstrap\URLRouterInterface
 *     route data returned by the url router must contain a 'response' element containing the
 *     dependency key for an instance of \\ZedBoot\\System\\Bootstrap\\ResponseInterface
 *     this is where request processing starts
 * 
 * every dependency configuration file except 'boot.php' will have access to the following,
 * available as variables and by dependency key:
 *   - $routeData ('request:routeData'): as returned by urlRouter->getRouteData()
 *   - $baseURL ('request:baseURL'): as returned by urlRouter->getBaseURL()
 *   - $urlParts ('request:urlParts'): as returned by urlRouter->getURLParts()
 *   - $urlParameters ('request:urlParameters'): as returned by urlRouter->getURLParameters()
 * 
 * Configuration files will also have access to any shared parameters defined in boot.php $parameters[sharedParameters]
 * These will be available as variables. For example - if boot.php has these parameters:
 * $parameters=
 * [
 * ...
 *     'foo'=>'bar',
 *     'baz'=>'zzz',
 * ...
 * ]
 * Other configuration files will have access to $foo (='bar') and $baz(='zzz')
 * Please note for shared parameters that some key names are reserved and will cause an exception or be overwritten:
 *  - parameters
 *  - services
 *  - factoryServices
 *  - __path
 *  - routeData
 *  - baseURL
 *  - urlParts
 *  - urlParameters
 * 
 */
use \ZedBoot\System\Error\ZBError as Err;
/**
 * @param $settingsDir String This is where dependency configuration files are found
 * @param $zbClassPath String ZedBoot root path of ZedBoot namespace
 */
function zbInit($settingsDir,$zbClassPath)
{
	$ok=true;
	$obStarted=false;
	try
	{
		//autoloader and dependency loader are hardwired here because they are neccessary to get everything else up and running
		//Set up the ZedBoot namespace
		$zbClassPath=rtrim($zbClassPath,'/');
		require_once $zbClassPath.'/System/Bootstrap/AutoLoader.class.php';
		$loader=new \ZedBoot\System\Bootstrap\Autoloader();
		$loader->register('ZedBoot',$zbClassPath.'/ZedBoot');

		//Set up the dependency loader
		$configLoader=new \ZedBoot\System\DI\DependencyConfigLoader();
		//$dependencyIndex finds and loads namespaced dependency configuration files as needed by $dependencyLoader
		$dependencyIndex=new \ZedBoot\System\DI\NamespacedDependencyIndex($configLoader, new \ZedBoot\System\DI\SimpleDependencyIndex(),$settingsDir);
		$dependencyLoader=new \ZedBoot\System\DI\SimpleDependencyLoader($dependencyIndex);
		
		$classRegistry=$dependencyLoader->getDependency('boot:classRegistry');
		$i=0;
		if(!is_array($classRegistry)) throw new Err('Invalid dependency parameter boot:classRegistry, expected array.');
		foreach($classRegistry as $k=>$item)
		{
			$i++;
			if(!is_array($item)) throw new Err('Invalid parameter: boot:classRegistry['.$k.'], all entries are expected to be arrays.');
			if(!array_key_exists('path',$item)) throw new Err('boot:classRegistry['.$k.'] missing path.');
			if(!array_key_exists('namespace',$item)) throw new Err('boot:classRegistry['.$k.'] missing namespace.');
			$loader->register(
				$item['namespace'],
				$item['path'],
				array_key_exists('suffix',$item)?$item['suffix']:'.class.php',
				array_key_exists('prefix',$item)?$item['prefix']:'');
		}
		
		//Get url router
		$router=$dependencyLoader->getDependency('boot:urlRouter','\\ZedBoot\\System\\Bootstrap\\URLRouterInterface');
		$configLoaderParameters=$dependencyLoader->getDependency('boot:sharedParameters','Array');
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
		$dependencyIndex->addParameters([
			'request:routeData'=>$routeData,
			'request:baseURL'=>$baseURL,
			'request:urlParts'=>$urlParts,
			'request:urlParameters'=>$urlParameters,
		]);
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
			if(count($header)<1) throw new Err('invalid header in list returned by '.get_class($response).'::getHeaders(): Each header array must have at least one element.');
		}
		foreach($headers as $header)
		{
			$h=array_shift($header);
			$replace=true;
			$responseCode=null;
			if(count($header)>0)$replace=array_shift($header);
			if(count($header)>0)$responseCode=array_shift($header);
			if($responseCode===null){ header($h,$replace); } else header($h,$replace,$responseCode);
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
