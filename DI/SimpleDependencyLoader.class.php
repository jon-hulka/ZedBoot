<?php

/**
 * Class SimpleDependencyLoader | ZedBoot/DI/SimpleDependencyLoader.class.php
 * @license     GNU General Public License, version 3
 * @package     DI
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2017, 2018, Jonathan Hulka
 */

/**
 * Dependency loader implementation
 * Provides a simple implementation of DependencyLoaderInterface
 */
/*
To test:
* getFactoryService
* circular dependencies
*  - service -> service
*  - service -> factory -> service
*  - factory -> factory
* id conflicts
* factory is not an object
* deeply nested errors involving services, factory services and parameters showing dependency path in error messages
* nested arguments
*/
namespace ZedBoot\DI;
use \ZedBoot\Error\ZBError as Err;
class SimpleDependencyLoader implements \ZedBoot\DI\DependencyLoaderInterface
{
	protected static
		//Expected results from gettype
		//Searching is faster on keys rather than values
		$types=[
			'boolean'=>true,
			'integer'=>true,
			'double'=>true,
			'string'=>true,
			'array'=>true,
			'object'=>true,
			'resource'=>true,
			'resource (closed)'=>true,
			'NULL'=>true,
			'unknown type'=>true
		];
	protected
		$dependencyIndex=null,
		$singletons=array();
	public function __construct(\ZedBoot\DI\DependencyIndexInterface $dependencyIndex)
	{
		$this->dependencyIndex=$dependencyIndex;
	}
	public function getDependency($id,$type=null)
	{
		$result=$this->loadDependency($id,array());
		if(!empty($type))
		{
			$t=gettype($result);
			if($t!==$type)
			{
				if($t==='object')
				{
					if(!($result instanceof $type)) throw new Err('Expected '.$type.', got '.get_class($result));
				}
				else throw new Err('Expected '.$type.', got '.$t);
			}
		}
		return $result;
	}
	protected function loadDependency($id,array $dependencyChain)
	{
		$result=false;
		if(array_key_exists($id,$this->singletons))
		{
			//Dependency has already been loaded
			$result=$this->singletons[$id];
		}
		else
		{
			$def=$this->dependencyIndex->getDependencyDefinition($id);
			switch($def['type'])
			{
				case 'parameter':
					$result=$def['value'];
					break;
				case 'service':
					$result=$this->loadService($id,$def,$dependencyChain);
					break;
				case 'factory service':
					$result=$this->loadFactoryService($id,$def,$dependencyChain);
					break;
			}
		}
		return $result;
	}

	/**
	 * @param $id
	 * @param array $dependencyChain dependencies for the current branch of the dependency tree. Used to detect circular dependencies.
	 */
	protected function loadService($id,array $def,array $dependencyChain)
	{
		$result=false;
		$argValues=array();
		//Make sure this service is not its own ancestor
		if(false!==(array_search($id,$dependencyChain,true)))
			throw new Err('Circular dependency: '.implode(' > ',$dependencyChain).' > '.$id);
		//Retrieve arguments needed by this service's constructor
		$dependencyChain[]=$id;
		$argValues=$this->extractArguments($def['args'],$dependencyChain);
		//Create a new instance
		$cn=$def['class_name'];
		$reflect=new \ReflectionClass($cn);
		$result=$reflect->newInstanceArgs($argValues);
		if($def['singleton']) $this->singletons[$id]=$result;
		return $result;
	}
	
	protected function loadFactoryService($id,array $def,array $dependencyChain)
	{
		$result=false;
		$factory=null;
		$argValues=array();
		$factoryId=$def['factory_id'];
		if(false!==(array_search($factoryId,$dependencyChain,true))) throw new Err('Circular dependency on factory service '.json_encode($id));
		$factory=$this->loadDependency($factoryId,$dependencyChain);
		if(!is_object($factory)) throw new Err('Factory '.json_encode($id).' is not an object.');
		$argValues=$this->extractArguments($def['args'],$dependencyChain);
		$result=call_user_func_array(array($factory,$def['function']),$args);
		if($def['singleton']) $this->singletons[$id]=$result;
		return $result;

	}
	protected function extractArguments(array $args, array $dependencyChain, $preserveKeys=false)
	{
		$argValues=array();
		$v=null;
		foreach($args as $k=>$arg)
		{
			if(is_array($arg))
			{
				//Recursively handle nested arrays
				$v=$this->extractArguments($arg,$dependencyChain,true);
			}
			else if(is_null($arg) || is_numeric($arg)  || is_bool($arg))
			{
				$v=$arg;
			}
			else if(is_scalar($arg))
			{
				$v=$this->loadDependency($arg,$dependencyChain);
			}
			else throw new Err('Loading '.implode(' > ',$dependencyChain).': Expected dependecy id (String), Array, NULL, or scalar constant (numeric, bool); got '.gettype($arg).'.');
			if($preserveKeys)
			{
				$argValues[$k]=$v;
			}
			else $argValues[]=$v;
		}
		return $argValues;
	}
}
