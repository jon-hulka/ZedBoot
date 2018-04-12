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
class DependencyConfigLoader implements \ZedBoot\System\Error\ErrorReporterInterface
{
	protected static
		$configFunction=null;
	protected
		$dependencyLoader=null,
		$error=null;
	public function getError(){ return $this->error; }
	public function __construct(\ZedBoot\System\DI\DependencyLoaderInterface $dependencyLoader){ $this->dependencyLoader=$dependencyLoader; }
	/**
	 * On first call - sets up static $configFunction. On subsequent calls - does nothing.
	 */
	public static function setConfigFunction($f){ if(empty(static::$configFunction)) static::$configFunction=$f; }
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
		$ok=true;
		$parameters=null;
		$services=null;
		$factoryServices=null;
		if($ok && !($ok=file_exists($path))) $this->error=get_class($this).'::'.__FUNCTION__.': Config file '.$path.' not found.';
		if($ok)
		{
			$cf=static::$configFunction;
			$cf($path,$parameters,$services,$factoryServices,$configParameters);
			if(!empty($parameters))
			{
				if(!($ok=is_array($parameters)))
				{
					$this->error=get_class($this).'::'.__FUNCTION__.': $parameters is not an array in config file '.$path.'.';
				}
				else if(!($ok=$this->dependencyLoader->addParameters($parameters))) $this->error=$this->dependencyLoader->getError();
			}
		}
		if($ok && !empty($services))
		{
			if(!($ok=is_array($services)))
			{
				$this->error=get_class($this).'::'.__FUNCTION__.': $services is not an array in config file '.$path.'.';
			}
			else $ok=$this->addServices($services,$path);
		}
		if($ok && !empty($factoryServices))
		{
			if(!($ok=is_array($factoryServices)))
			{
				$this->error=get_class($this).'::'.__FUNCTION__.': $factoryServices is not an array in config file '.$path.'.';
			}
			else $ok=$this->addFactoryServices($factoryServices,$path);
		}
		return $ok;
	}
	protected function addServices($services,$path)
	{
		$ok=true;
		if($ok) foreach($services as $id=>$params)
		{
			$prefix=get_class($this).'::'.__FUNCTION__.': config file '.$path.': service '.json_encode($id);
			if($ok && !($ok=is_array($params))) $this->error=$prefix.' is not specified by an array.';
			if($ok && !($ok=(count($params)>0))) $this->error=$prefix.' must have at least 1 parameter (className)';
			if($ok)
			{
				$delim='';
				$err='';
				if(count($params)>1 && !(is_null($params[1]) || is_array($params[1])))
				{
					$ok=false;
					$err.=$delim.'second parameter (arguments, optional) must be null or array';
					$delim=', ';
				}
				if(count($params)>2 && getType($params[2])!=='boolean')
				{
					$ok=false;
					$err.=$delim.'third parameter (singleton, optional) must be boolean';
					$delim=', ';
				}
				if(!$ok) $this->error=$prefix.': '.$err;
			}
			if($ok)
			{
				$className=$params[0];
				$args=empty($params[1])?null:$params[1];
				$singleton=true;
				if((count($params)>2)) $singleton=$params[2];
				if(!($ok=$this->dependencyLoader->addService($id,$className,$args,$singleton))) $this->error=$prefix.': '.$this->dependencyLoader->getError();
			}
			if(!$ok) break;
		}
		return $ok;
	}
	protected function addFactoryServices($factoryServices,$path)
	{
		$ok=true;
		if($ok) foreach($factoryServices as $id=>$params)
		{
			$prefix=get_class($this).'::'.__FUNCTION__.': config file '.$path.': factory service '.json_encode($id);
			if($ok && !($ok=is_array($params))) $this->error=$prefix.' is not specified by an array.';
			if($ok && !($ok=(count($params)>1))) $this->error=$prefix.' must have at least 2 parameters (factory id and factory function)';
			if($ok)
			{
				$delim='';
				$err='';
				if(count($params)>2 && !(is_null($params[2]) || is_array($params[2])))
				{
					$ok=false;
					$err.=$delim.'third parameter (arguments, optional) must be null or array';
					$delim=', ';
				}
				if(count($params)>3 && getType($params[3])!=='boolean')
				{
					$ok=false;
					$err.=$delim.'fourth parameter (singleton, optional) must be boolean';
					$delim=', ';
				}
				if(!$ok) $this->error=$prefix.': '.$err;
			}
			if($ok)
			{
				$factoryId=$params[0];
				$function=$params[1];
				$args=empty($params[2])?null:$params[2];
				$singleton=true;
				if((count($params)>3)) $singleton=$params[3];
				if(!($ok=$this->dependencyLoader->addFactoryService($id,$factoryId,$function,$args,$singleton))) $this->error=$prefix.': '.$this->dependencyLoader->getError();
			}
			if(!$ok) break;
		}
		return $ok;
	}
}
//Config fuction is defined outside the DependencyConfigLoader's private scope, keeping DependencyConfigLoader insulated from the configuration file
DependencyConfigLoader::setConfigFunction(function($path,&$parameters,&$services,&$factoryServices,Array $configParameters=null)
{
	if(is_array($configParameters)) extract($configParameters);
	include $path;
});
