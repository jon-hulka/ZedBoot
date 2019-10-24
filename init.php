<?php
/* There should be dependency configuration file located at $configDir.'/'.$bootConfigKey.'.php'
 * It is expected to have the following:
 * $parameters=
 * [
 *     'classRegistry' =>
 *     [
 *         [
 *             'path'=> <string, required>, 
 *             'namespace' => <string, required>,
 *             `suffix' => <string (filename suffix), optonal, defaults to '.class.php'>,
 *             'prefix' => <string (filename prefix), optonal, defaults to ''>
 *         ],
 *        ...
 *     ],
 *     'sharedParameters => 
 *     [
 *         //Anything to be shared with other config files (more information below)   
 *     ],
 *     ...
 * ];
 * $services:
 *   - service definition for urlRouter, implementing \ZedBoot\Bootstrap\URLRouterInterface
 *     route data returned by the url router must contain a 'response' element containing the
 *     dependency key for an instance of \ZedBoot\Bootstrap\ResponseInterface
 *     This is where request processing starts.
 * 
 * at the time that response is loaded, the following dependency keys will be available:
 *   - request:routeData     - as returned by urlRouter->getRouteData()
 *   - request:baseURL       - as returned by urlRouter->getBaseURL()
 *   - request:urlParts      - as returned by urlRouter->getURLParts()
 *   - request:urlParameters - as returned by urlRouter->getURLParameters()
 * 
 * Configuration files will also have access to any shared parameters defined in the boot config script $parameters['sharedParameters']
 * These will be available as variables. For example - if the boot config script has these parameters:
 * $parameters=
 * [
 * ...
 *     'sharedParameters'=>
 *     [
 *         ...
 *         'foo'=>'bar',
 *         'baz'=>'zzz',
 *         ...
 *     ]
 * ...
 * ]
 * Other configuration files will have access to $foo (='bar') and $baz (='zzz')
 * Note for shared parameters that some key names are reserved and will cause an exception or be overwritten:
 *  - parameters
 *  - services
 *  - factoryServices
 *  - includes
 *  - __path
 */
use \ZedBoot\Error\ZBError as Err;
/**
 * @param $configDir String This is where dependency configuration files are found
 * @param $bootConfigKey dependency key for boot configuration. This correlates to a .php file located within the configuration directory.
 * @param $zbClassPath String ZedBoot root path of ZedBoot namespace
 */
function zbInit($configDir,$bootConfigKey,$zbClassPath)
{
	$ok=true;
	$obStarted=false;
	try
	{
		//autoloader and dependency loader are hardwired here because they are neccessary to get everything else up and running
		//Set up the ZedBoot namespace
		$zbClassPath=rtrim($zbClassPath,'/');
		require_once $zbClassPath.'/Bootstrap/AutoLoader.class.php';
		$loader=new \ZedBoot\Bootstrap\Autoloader();
		$loader->register('ZedBoot',$zbClassPath);

		//Set up the dependency loader
		$configLoader=new \ZedBoot\DI\DependencyConfigLoader();

		//$dependencyIndex finds and loads namespaced dependency configuration files as needed by $dependencyLoader
		$dependencyIndex=new \ZedBoot\DI\NamespacedDependencyIndex($configLoader, new \ZedBoot\DI\SimpleDependencyIndex(),$configDir);
		$dependencyLoader=new \ZedBoot\DI\SimpleDependencyLoader($dependencyIndex);
		
		//Make sure shared parameters are available as soon as possible
		//They are likely to be used by the url router
		$configLoaderParameters=$dependencyLoader->getDependency($bootConfigKey.':sharedParameters','array');
		$configLoader->setConfigParameters($configLoaderParameters);

		$classRegistry=$dependencyLoader->getDependency($bootConfigKey.':classRegistry','array');
		$i=0;
		foreach($classRegistry as $k=>$item)
		{
			$i++;
			if(!is_array($item)) throw new Err('Invalid parameter: '.$bootConfigKey.':classRegistry['.$k.'], all entries are expected to be arrays.');
			if(!array_key_exists('path',$item)) throw new Err($bootConfigKey.':classRegistry['.$k.'] missing path.');
			if(!array_key_exists('namespace',$item)) throw new Err($bootConfigKey.':classRegistry['.$k.'] missing namespace.');
			$loader->register(
				$item['namespace'],
				$item['path'],
				array_key_exists('suffix',$item)?$item['suffix']:'.class.php',
				array_key_exists('prefix',$item)?$item['prefix']:'');
		}
		
		//Get url router
		$router=$dependencyLoader->getDependency($bootConfigKey.':urlRouter','\\ZedBoot\\Bootstrap\\URLRouterInterface');
		//Resolve the route
		$url=explode('?',$_SERVER['REQUEST_URI'],2);
		$router->parseURL($url[0]);

		//Load route data
		$routeData=$router->getRouteData();
		$baseURL=$router->getBaseURL();
		if(!array_key_exists('response',$routeData)) throw new Err('response dependency not specified for route '.$baseURL);

		$dependencyIndex->addParameters([
			'request:routeData'=>$routeData,
			'request:baseURL'=>$baseURL,
			'request:urlParts'=>$router->getURLParts(),
			'request:urlParameters'=>$router->getURLParameters(),
		]);

		//Get the request handler
		$response=$dependencyLoader->getDependency($routeData['response'],'\\ZedBoot\\Bootstrap\\ResponseInterface');
		
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
			if(count($header)<1) throw new Err('Invalid header in list returned by '.get_class($response).'::getHeaders(): Each header array must have at least one element.');
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
