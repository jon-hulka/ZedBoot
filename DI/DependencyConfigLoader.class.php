<?php
/**
 * Class DependencyConfigLoader | ZedBoot/DI/DependencyConfigLoader.class.php
 * @license     GNU General Public License, version 3
 * @package     DI
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2017 - 2020 Jonathan Hulka
 */

/**
 * Configuration file loader
 * Adds configuration details from a PHP configuration file to an implementation of DependencyIndexInterface
 */

namespace ZedBoot\DI;
use \ZedBoot\Error\ZBError as Err;
class DependencyConfigLoader
{
	protected static
		$configFunction=null;
	protected
		$configParameters=array();
	/**
	 * On first call - sets up static $configFunction. On subsequent calls - does nothing.
	 */
	public static function setConfigFunction($f){ if(empty(static::$configFunction)) static::$configFunction=$f; }
	/**
	 * Specify a set of configuration parameters to be included with every call to loadConfig()
	 * These will be available to the config script as variables
	 * '__path', 'parameters', 'services', 'factoryServices', and 'includes' are not permitted as keys, if any appear an exception will be thrown
	 */
	public function setConfigParameters(array $configParameters)
	{
		$this->configParameters=$configParameters;
	}
	/**
	 * The config file can have any of the following:
	 *  - $parameters - [id => value, ...]
	 *  - $services - [id => [className, optional arguments array, optional singleton boolean], ...]
	 *  - $factoryServices - [id => [factory id, factory function name, optional arguments array, optional singleton boolean], ...]
	 *  - $includes - paths (absolute or relative) to config files that will be loaded as if their contents belong to the script at $path
	 *  - $setterInjections - [['id'=><dependency id>,'function'=><function name>,'args'=><arguments array>]]
	 * For more details, see DependencyIndexInterface
	 * @param string $path file to load
	 */
	public function loadConfig(\ZedBoot\DI\DependencyIndexInterface $dependencyIndex,$path)
	{
		$parameters=null;
		$services=null;
		$factoryServices=null;
		$includes=null;
		if(!file_exists($path)) throw new Err('Config file '.$path.' not found.');
		$cf=static::$configFunction;
		$cf($path,$parameters,$services,$factoryServices,$includes,$setterInjections,$this->configParameters);
		if(!empty($parameters))
		{
			if(!is_array($parameters)) throw new Err('$parameters is not an array in config file '.$path.'.');
			$dependencyIndex->addParameters($parameters);
		}
		if(!empty($services))
		{
			if(!is_array($services)) throw new Err('$services is not an array in config file '.$path.'.');
			$this->addServices($dependencyIndex,$services,$path);
		}
		if(!empty($factoryServices))
		{
			if(!is_array($factoryServices)) throw new Err('$factoryServices is not an array in config file '.$path.'.');
			$this->addFactoryServices($dependencyIndex,$factoryServices,$path);
		}
		if(!empty($includes))
		{
			if(!is_array($includes)) throw new Err('$includes is not an array in config file '.$path.'.');
			foreach($includes as $includePath)
			{
				if(substr($includePath,0,1)!=='/')
				{
					//try to resolve relative paths
					$pathParts=explode('/',trim(dirname($path),'/'));
					$includePathParts=explode('/',trim($includePath,'/'));
					while($part=array_shift($includePathParts))
					{
						if($part=='..')
						{
							array_pop($pathParts);
						}
						else $pathParts[]=$part;
					}
					$includePath='/'.implode('/',$pathParts);
				}
				$this->loadConfig($dependencyIndex,$includePath);
			}
		}
		if(!empty($setterInjections))
		{
			if(!is_array($setterInjections)) throw new Err('$setterInjections is not an array in config file '.$path.'.');
			$this->addSetterInjections($dependencyIndex, $setterInjections);
		}
	}
	protected function addServices($dependencyIndex,$services,$path)
	{
		foreach($services as $id=>$params)
		{
			$prefix='Config file '.$path.': service '.json_encode($id);
			if(!is_array($params)) throw new Err($prefix.' is not specified by an array.');
			if(count($params)<1) throw new Err($prefix.' must have at least 1 parameter (className)');
			if(count($params)>1 && !(is_null($params[1]) || is_array($params[1]))) throw new Err($delm.' second parameter (arguments, optional) must be null or array');
			if(count($params)>2 && getType($params[2])!=='boolean') throw new Err($delim.'third parameter (singleton, optional) must be boolean');
			$className=$params[0];
			$args=empty($params[1])?null:$params[1];
			$singleton=true;
			if((count($params)>2)) $singleton=$params[2];
			$dependencyIndex->addService($id,$className,$args,$singleton);
		}
	}
	protected function addFactoryServices($dependencyIndex,$factoryServices,$path)
	{
		foreach($factoryServices as $id=>$params)
		{
			$prefix='Config file '.$path.': factory service '.json_encode($id);
			if(!is_array($params)) throw new Err($prefix.' is not specified by an array.');
			if(count($params)<2) throw new Err($prefix.' must have at least 2 parameters (factory id and factory function)');
			if(count($params)>2 && !(is_null($params[2]) || is_array($params[2]))) throw new Err($prefix.'third parameter (arguments, optional) must be null or array');
			if(count($params)>3 && getType($params[3])!=='boolean') throw new Err($delim.'fourth parameter (singleton, optional) must be boolean');
			$factoryId=$params[0];
			$function=$params[1];
			$args=empty($params[2])?null:$params[2];
			$singleton=true;
			if((count($params)>3)) $singleton=$params[3];
			$dependencyIndex->addFactoryService($id,$factoryId,$function,$args,$singleton);
		}
	}
	protected function addSetterInjections($dependencyIndex, $setterInjections)
	{
		foreach($setterInjections as $params)
		{
			if(!array_key_exists('id',$params) || !array_key_exists('function',$params) || !array_key_exists('args',$params))
			{
				throw new Err('$setterInjections items must have id, function, and args');
			}
			$dependencyIndex->addSetterInjection($params['id'],$params['function'],$params['args']);
		}
	}
}
//Config fuction is defined outside the DependencyConfigLoader's private scope, keeping it insulated from the configuration file
DependencyConfigLoader::setConfigFunction(function($__path,&$parameters,&$services,&$factoryServices,&$includes,&$setterInjections,Array $configParameters)
{
	foreach(array('__path','parameters','services','factoryServices','includes','setterInjections') as $k) if(array_key_exists($k,$configParameters)) throw new Err('\''.$k.'\' cannot be a key in $configParameters');
	unset($k);
	extract($configParameters);
	include $__path;
});
