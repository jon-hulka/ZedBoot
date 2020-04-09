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
		$ordinals=['1st','2nd','3rd','4th','5th','6th','7th'],
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
	 * '__path', 'parameters', 'arrayValues', 'objectProperties', 'services', 'factoryServices', and 'includes' are not permitted as keys, if any appear an exception will be thrown
	 */
	public function setConfigParameters(array $configParameters)
	{
		$this->configParameters=$configParameters;
	}
	/**
	 * The config file can have any of the following:
	 *  - $parameters - [id => value, ...]
	 *  - $arrayValues - [ id => [<dependency id>,<array key>],...]
	 *  - $objectProperties - [ id => [<dependency id>,<property name>], ...]]
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
		$arrayValues=null;
		$objectProperties=null;
		$services=null;
		$factoryServices=null;
		$includes=null;
		$setterInjections=null;
		if(!file_exists($path)) throw new Err('Config file '.$path.' not found.');
		$cf=static::$configFunction;
		$cf($path,$parameters,$arrayValues,$objectProperties,$services,$factoryServices,$includes,$setterInjections,$this->configParameters);
		if($parameters!==null)
		{
			if(!is_array($parameters)) throw new Err('$parameters is not an array in config file '.$path.'.');
			$dependencyIndex->addParameters($parameters);
		}
		if($arrayValues!==null)
		{
			if(!is_array($arrayValues)) throw new Err('$arrayValues is not an array in config file '.$path.'.');
			$this->addArrayValues($dependencyIndex,$arrayValues,$path);
		}
		if($objectProperties!==null)
		{
			if(!is_array($objectProperties)) throw new Err('$objectProperties is not an array in config file '.$path.'.');
			$this->addObjectProperties($dependencyIndex,$objectProperties,$path);
		}
		if($services!==null)
		{
			if(!is_array($services)) throw new Err('$services is not an array in config file '.$path.'.');
			$this->addServices($dependencyIndex,$services,$path);
		}
		if($factoryServices!==null)
		{
			if(!is_array($factoryServices)) throw new Err('$factoryServices is not an array in config file '.$path.'.');
			$this->addFactoryServices($dependencyIndex,$factoryServices,$path);
		}
		if($includes!==null)
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
		if($setterInjections!==null)
		{
			if(!is_array($setterInjections)) throw new Err('$setterInjections is not an array in config file '.$path.'.');
			$this->addSetterInjections($dependencyIndex, $setterInjections, $path);
		}
	}
	protected function addServices($dependencyIndex, $services, $path)
	{
		$className=null;
		$args=null;
		$singleton=null;
		$spec=
		[
			['className',['string']],
			['arguments',['array'],[]],
			['singleton',['boolean'],true] 
		];
		foreach($services as $id=>$params)
		{
			$prefix='Config file '.$path.': services: '.json_encode($id).':';
			[$className,$args,$singleton] = $this->extractParameters( $params, 1, $spec, $prefix );
			$dependencyIndex->addService($id,$className,$args,$singleton);
		}
	}
	protected function addArrayValues($dependencyIndex, $arrayValues, $path)
	{
		$arrayId=null;
		$arrayKey=null;
		$ifNotExists=null;
		$spec=
		[
			['arrayId',['string']],
			['arrayKey',['string','integer']],
			['ifNotExists',['string','integer','double','boolean','array','null'],null]
		];
		foreach($arrayValues as $id=>$params)
		{
			$prefix='Config file '.$path.': arrayValues: '.json_encode($id).':';
			[ $arrayId, $arrayKey, $ifNotExists ] = $this->extractParameters( $params, 2, $spec, $prefix );
			$dependencyIndex->addArrayValue($id,$arrayId,$arrayKey,$ifNotExists);
		}
	}

	protected function addObjectProperties($dependencyIndex, $objectProperties, $path)
	{
		$objectId=null;
		$propertyName=null;
		$ifNotExists=null;
		$spec=
		[
			['objectId',['string']],
			['propertyName',['string']],
			['ifNotExists',['string','integer','double','boolean','array','null'],null]
		];
		foreach($objectProperties as $id=>$params)
		{
			$prefix='Config file '.$path.': objectProperties: '.json_encode($id).':';
			[ $objectId, $propertyName, $ifNotExists ] = $this->extractParameters( $params, 2, $spec, $prefix );
			$dependencyIndex->addObjectProperty($id,$objectId,$propertyName,$ifNotExists);
		}
	}
	protected function addFactoryServices($dependencyIndex, $factoryServices, $path)
	{
		$factoryId=null;
		$function=null;
		$args=null;
		$singleton=null;
		$spec=
		[
			['factoryId',['string']],
			['function',['string']],
			['args',['array'],[]],
			['singleton',['boolean'],true]
		];
		foreach($factoryServices as $id=>$params)
		{
			$prefix='Config file '.$path.': factory service '.json_encode($id);
			[$factoryId,$function,$args,$singleton] = $this->extractParameters( $params, 2, $spec, $prefix );
			$dependencyIndex->addFactoryService($id,$factoryId,$function,$args,$singleton);
		}
	}
	protected function addSetterInjections($dependencyIndex, $setterInjections, $path)
	{
		$serviceId=null;
		$function=null;
		$args=null;
		$prefix='Config file '.$path.': setter injections: ';
		$spec=
		[
			['serviceId',['string']],
			['function',['string']],
			['args',['array'],[]]
		];
		foreach($setterInjections as $params)
		{
			[ $serviceId, $function, $args] = $this->extractParameters( $params, 2, $spec, $prefix );
			$dependencyIndex->addSetterInjection($serviceId,$function,$args);
		}
	}
	protected function extractParameters
	(
		$params,
		int $requiredCount,
		array $spec,
		string $errorPrefix
	)
	{
		$result=[];
		$i=0;
		$err='';
		$errParts=[];
		if(!is_array($params)) throw new Err($errorPrefix.' is not specified by an array.');
		if(count($params)<$requiredCount)
		{
			$err=$errorPrefix.' must have at least '.$requiredCount.' parameter'.($requiredCount===1?'':'s').' ( ';
			$errParts=[];
			$i=0;
			foreach($spec as $s) $errParts[]=(++$i > $requiredCount ? 'optional ' : '').$s[0];
			$err.=implode(', ',$errParts).' )';
			throw new Err($err);
		}
		if(count($params)>count($spec))
		{
			$err=$errorPrefix.' must have no more than '.count($spec).' parameter'.($requiredCount===1?'':'s').' ( ';
			$errParts=[];
			$i=0;
			foreach($spec as $s) $errParts[]=(++$i > $requiredCount ? 'optional ' : '').$s[0];
			$err.=implode(', ',$errParts).' )';
			throw new Err($err);
		}
		$i=0;
		foreach($spec as $s)
		{
			if(count($params))
			{
				$v=array_shift($params);
				$t=gettype($v);
				if(count($s[1]) && array_search($t,$s[1])===false) throw new Err
				(
					$errorPrefix.' '.static::$ordinals[$i].' parameter ('.$s[0].($i>$requiredCount ? ', optional':'').') expected '.implode(' | ',$s[1]).', got '.$t.'.'
				);
				$result[]=$v;
			}
			else $result[]=$s[2];
			$i++;
		}
		return $result;
	}
}
//Config loader fuction is defined outside DependencyConfigLoader's private scope, keeping DependencyConfigLoader insulated from the configuration file
DependencyConfigLoader::setConfigFunction
(
	function
	(
		$__path,
		&$parameters,
		&$arrayValues,
		&$objectProperties,
		&$services,
		&$factoryServices,
		&$includes,
		&$setterInjections,
		array $configParameters
	)
	{
		foreach
		(
			[
				'__path',
				'parameters',
				'arrayValues',
				'objectProperties',
				'services',
				'factoryServices',
				'includes',
				'setterInjections'
			]
			as $k
		) if(array_key_exists($k,$configParameters)) throw new Err('\''.$k.'\' cannot be a key in $configParameters');
		unset($k);
		extract($configParameters);
		include $__path;
	}
);
