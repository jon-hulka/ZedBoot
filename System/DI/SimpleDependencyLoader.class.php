<?php

/**
 * Class SimpleDependencyLoader | ZedBoot/System/DI/SimpleDependencyLoader.class.php
 * @license     GNU General Public License, version 3
 * @package     System
 * @subpackage  DI
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2017, Jonathan Hulka
 */

/**
 * Dependency loader implementation
 * Provides a simple implementation of DependencyLoaderInterface
 */
//To do: consider replacing nested dependency errors with dependency chain prefix
/*
To test:
* getFactoryService
* circular dependencies
*  - service -> service
*  - service -> factory -> service
*  - factory -> factory
* id conflicts
* factory doesn't implement ErrorReporterInterface
* factory is not an object
* deeply nested errors involving services, factory services and parameters showing dependency path in error messages
* nested arguments
*/
namespace ZedBoot\System\DI;
use \ZedBoot\System\Error\ZBError as Err;
class SimpleDependencyLoader implements \ZedBoot\System\DI\DependencyLoaderInterface
{
	protected
		$configFunction=null,
		$definitions=array(),
		$singletons=array();
	public function addParameters(array $parameters)
	{
		foreach($parameters as $id=>$def) $this->addDefinition($id,array(
				'type'=>'parameter',
				'value'=>$def
			));
	}
	public function addService($id,$className,array $arguments=null,$singleton=true)
	{
		if(empty($arguments)) $arguments=array();
		$this->addDefinition($id,array(
			'type'=>'service',
			'class_name'=>$className,
			'args'=>$arguments,
			'singleton'=>$singleton
		));
	}
	public function addFactoryService($id,$factoryId,$function,array $arguments=null,$singleton=true)
	{
		if(empty($arguments)) $arguments=array();
		$this->addDefinition($id,array(
			'type'=>'factory service',
			'factory_id'=>$factoryId,
			'function'=>$function,
			'args'=>$arguments,
			'singleton'=>$singleton
		));
	}
	public function getDependency($id,$classType=null)
	{
		$v=null;
		$result=$this->loadDependency($id,array());
		if(!empty($classType))
		{
			if(!is_object($result))
			{
				throw new Err('Expected object of type '.$classType.', got non-object');
			}
			else if(!($result instanceof $classType)) throw new Err('Expected object of type '.$classType.', got '.get_class($result));
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
			if(!array_key_exists($id,$this->definitions)) throw new Err('Attempt to get undefined dependency: '.json_encode($id).'.');
			switch($this->definitions[$id]['type'])
			{
				case 'parameter':
					$result=$this->definitions[$id]['value'];
					break;
				case 'service':
					$result=$this->loadService($id,$dependencyChain);
					break;
				case 'factory service':
					$result=$this->loadFactoryService($id,$dependencyChain);
					break;
			}
		}
		return $result;
	}

	/**
	 * @param $id
	 * @param array $dependencyChain dependencies for the current branch of the dependency tree. Used to detect circular dependencies.
	 */
	protected function loadService($id,array $dependencyChain)
	{
		$result=false;
		$def=null;
		$argValues=array();
		//Make sure this service is not its own ancestor
		if(false!==(array_search($id,$dependencyChain,true)))
			throw new Err('Circular dependency: '.implode(' > ',$dependencyChain).' > '.$id);
		//Retrieve arguments needed by this service's constructor
		$def=$this->definitions[$id];
		$dependencyChain[]=$id;
		$argValues=$this->extractArguments($def['args'],$dependencyChain);
		//Create a new instance
		$cn=$def['class_name'];
		$reflect=new \ReflectionClass($cn);
		$result=$reflect->newInstanceArgs($argValues);
		if($def['singleton']) $this->singletons[$id]=$result;
		return $result;
	}
	
	protected function loadFactoryService($id,array $dependencyChain)
	{
		$result=false;
		$def=null;
		$factoryId=null;
		$factory=null;
		$argValues=array();
		$def=$this->definitions[$id];
		$factoryId=$def['factory_id'];
		if(false!==(array_search($factoryId,$dependencyChain,true))) throw new Err('Circular dependency on factory service '.json_encode($id));
		$factory=$this->loadDependency($factoryId,$dependencyChain);
		if(!is_object($factory)) throw new Err('Factory '.json_encode($id).' is not an object.');
		$argValues=$this->extractArguments($def['args'],$dependencyChain);
		$result=call_user_func_array(array($factory,$def['function']),$args);
		if($def['singleton']) $this->singletons[$id]=$result;
		return $result;

	}
	protected function addDefinition($id,$definition)
	{
		if(array_key_exists($id,$this->definitions)) throw new Err($definition['type'].' id '.json_encode($id).' conflicts with existing '.$this->definitions[$id]['type']);
		$this->definitions[$id]=$definition;
	}
	protected function extractArguments(array $args, array $dependencyChain, $preserveKeys=false)
	{
		$argValues=array();
		foreach($args as $k=>$arg)
		{
			if(is_array($arg))
			{
				//Recursively handle nested arrays
				$v=$this->extractArguments($arg,$dependencyChain,true);
			}
			else
			{
				if(!is_scalar($arg)) throw new Err('Encountered non-array, non-scalar argument specification.');
				$v=$this->loadDependency($arg,$dependencyChain);
			}
			if($preserveKeys)
			{
				$argValues[$k]=$v;
			}
			else $argValues[]=$v;
		}
		return $argValues;
	}
}
