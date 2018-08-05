<?php
/**
 * Class DependencyConfigLoader | ZedBoot/System/DI/DependencyConfigLoader.class.php
 * @license     GNU General Public License, version 3
 * @package     System
 * @subpackage  DI
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2017, Jonathan Hulka
 */

/**
 * Configuration file loader
 * Adds configuration details from a .php configuration file to an instance of DependencyLoaderInterface
 */

namespace ZedBoot\System\DI;
use \ZedBoot\System\Error\ZBError as Err;
class DependencyConfigLoader
{
	protected static
		$configFunction=null;
	protected
		$dependencyLoader=null,
		$configParameters=array();
	public function __construct(\ZedBoot\System\DI\DependencyLoaderInterface $dependencyLoader){ $this->dependencyLoader=$dependencyLoader; }
	/**
	 * On first call - sets up static $configFunction. On subsequent calls - does nothing.
	 */
	public static function setConfigFunction($f){ if(empty(static::$configFunction)) static::$configFunction=$f; }
	/**
	 * Specify a set of configuration parameters to be included with every call to loadConfig()
	 * In case of name conflicts, parameters passed to loadConfig will take priority.
	 */
	public function setConfigParameters(array $configParameters)
	{
		$this->configParameters=$configParameters;
	}
	/**
	 * The config file can have three arrays:
	 *  - $parameters - id/value pairs
	 *  - $services - id/array(className,optional arguments array, optional singleton boolean)
	 *  - $factoryServices - id/array(factory id, factory function, optional arguments array, optional singleton boolean)
	 * @param string $path file to load
	 * @param array $configParameters optional parameters to pass to the config file - they will appear as variables
	 * @return boolean error status
	 */
	public function loadConfig($path,array $configParameters=null)
	{
		$parameters=null;
		$services=null;
		$factoryServices=null;
		if(!file_exists($path)) throw new Err('Config file '.$path.' not found.');
		$cf=static::$configFunction;
		$cf($path,$parameters,$services,$factoryServices,array_merge(is_null($configParameters)?array():$configParameters,$this->configParameters));
		if(!empty($parameters))
		{
			if(!is_array($parameters)) throw new Err('$parameters is not an array in config file '.$path.'.');
			$this->dependencyLoader->addParameters($parameters);
		}
		if(!empty($services))
		{
			if(!is_array($services)) throw new Err('$services is not an array in config file '.$path.'.');
			$this->addServices($services,$path);
		}
		if(!empty($factoryServices))
		{
			if(!is_array($factoryServices)) throw new Err('$factoryServices is not an array in config file '.$path.'.');
			$this->addFactoryServices($factoryServices,$path);
		}
	}
	protected function addServices($services,$path)
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
			$this->dependencyLoader->addService($id,$className,$args,$singleton);
		}
	}
	protected function addFactoryServices($factoryServices,$path)
	{
		foreach($factoryServices as $id=>$params)
		{
			$prefix='Config file '.$path.': factory service '.json_encode($id);
			if(!is_array($params)) throw new Err($prefix.' is not specified by an array.');
			if(count($params)<2) throw new Err($prefix.' must have at least 2 parameters (factory id and factory function)');
			if(count($params)>2 && !(is_null($params[2]) || is_array($params[2]))) throw new Err($delim.'third parameter (arguments, optional) must be null or array');
			if(count($params)>3 && getType($params[3])!=='boolean') throw new Err($delim.'fourth parameter (singleton, optional) must be boolean');
			$factoryId=$params[0];
			$function=$params[1];
			$args=empty($params[2])?null:$params[2];
			$singleton=true;
			if((count($params)>3)) $singleton=$params[3];
			$this->dependencyLoader->addFactoryService($id,$factoryId,$function,$args,$singleton);
		}
	}
}
//Config fuction is defined outside the DependencyConfigLoader's private scope, keeping DependencyConfigLoader insulated from the configuration file
DependencyConfigLoader::setConfigFunction(function($path,&$parameters,&$services,&$factoryServices,Array $configParameters=null)
{
	if(is_array($configParameters)) extract($configParameters);
	include $path;
});
