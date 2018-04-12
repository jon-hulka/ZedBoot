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
class SimpleDependencyLoader implements \ZedBoot\System\DI\DependencyLoaderInterface
{
	protected
		$configFunction=null,
		$definitions=array(),
		$singletons=array(),
		$error=null;
	public function getError(){ return $this->error; }
	public function addParameters(array $parameters)
	{
		$ok=true;
		foreach($parameters as $id=>$def)
			if(!($ok=$this->addDefinition($id,array(
				'type'=>'parameter',
				'value'=>$def
			)))) break;
		return $ok;
	}
	public function addService($id,$className,array $arguments=null,$singleton=true)
	{
		if(empty($arguments)) $arguments=array();
		return $this->addDefinition($id,array(
			'type'=>'service',
			'class_name'=>$className,
			'args'=>$arguments,
			'singleton'=>$singleton
		));
	}
	public function addFactoryService($id,$factoryId,$function,array $arguments=null,$singleton=true)
	{
		if(empty($arguments)) $arguments=array();
		return $this->addDefinition($id,array(
			'type'=>'factory service',
			'factory_id'=>$factoryId,
			'function'=>$function,
			'args'=>$arguments,
			'singleton'=>$singleton
		));
	}
	public function getDependency($id,&$result,$classType=null)
	{
		$ok=false;
		$v=null;
		if(array_key_exists($id,$this->singletons))
		{
			$ok=true;
			$result=$this->singletons[$id];
		}
		else
		{
			$ok=$this->loadDependency($id,array(),$v);
			if($ok) $result=$v;
		}
		if($ok && !empty($classType))
		{
			if(!is_object($result))
			{
				$ok=false;
				$this->error=get_class($this).'::'.__FUNCTION__.': Expected object of type '.$classType.', got non-object';
			}
			else if(!($result instanceof $classType))
			{
				$ok=false;
				$this->error=get_class($this).'::'.__FUNCTION__.': Expected object of type '.$classType.', got '.get_class($result);
			}
		}
		return $ok;
	}
	protected function loadDependency($id,array $dependencyChain,&$result)
	{
		$ok=true;
		$service=null;
//		$dependencyChain[]=$id;
		if($ok && !$ok=(array_key_exists($id,$this->definitions))) $this->error=get_class($this).'::'.__FUNCTION__.': Attempt to get undefined dependency: '.json_encode($id).'.';
		if($ok)
		{
			switch($this->definitions[$id]['type'])
			{
				case 'parameter':
					$result=$this->definitions[$id]['value'];
					break;
				case 'service':
					$ok=false!==($service=$this->loadService($id,$dependencyChain));
					if($ok) $result=$service;
					break;
				case 'factory service':
					$ok=false!==($service=$this->loadFactoryService($id,$dependencyChain));
					if($ok) $result=$service;
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
		$ok=true;
		$result=false;
		$def=null;
		$argValues=array();
		//Make sure this service is not its own ancestor
		if($ok && !($ok=false===(array_search($id,$dependencyChain,true))))
			$this->error=get_class($this).'::'.__FUNCTION__.': Circular dependency: '.implode(' > ',$dependencyChain).' > '.$id;
		//Retrieve arguments needed by this service's constructor
		if($ok)
		{
			$def=$this->definitions[$id];
			$dependencyChain[]=$id;
			if(!($ok=false!==($argValues=$this->extractArguments($def['args'],$dependencyChain))))
				$this->error=get_class($this).'::'.__FUNCTION__.': Getting service '.json_encode($id).': '.$this->error;
		}
		//Create a new instance
		if($ok)
		{
			$cn=$def['class_name'];
			$reflect=new \ReflectionClass($cn);
			$result=$reflect->newInstanceArgs($argValues);
			if($def['singleton']) $this->singletons[$id]=$result;
		}
		return $result;
	}
	
	protected function loadFactoryService($id,array $dependencyChain)
	{
		$ok=true;
		$result=false;
		$def=null;
		$factoryId=null;
		$factory=null;
		$argValues=array();
		if($ok)
		{
			$def=$this->definitions[$id];
			$factoryId=$def['factory_id'];
			if(!($ok=false===(array_search($factoryId,$dependencyChain,true))))
				$this->error=get_class($this).'::'.__FUNCTION__.': Circular dependency on factory service '.json_encode($id);
		}
		if($ok) $ok=$this->loadDependency($factoryId,$dependencyChain,$factory);
		if($ok && !($ok=is_object($factory))) $this->error=get_class($this).'::'.__FUNCTION__.': Factory '.json_encode($id).' is not an object.';
		if($ok && !($ok=false!==($argValues=$this->extractArguments($def['args'],$dependencyChain))))
			$this->error=get_class($this).'::'.__FUNCTION__.': Getting factory service '.json_encode($id).': '.$this->error;
		if($ok && !$ok=false!==($result=call_user_func_array(array($factory,$def['function']),$args)))
		{
			//Hopefully the factory class implements getError()
			$this->error=get_class($this).'::'.__FUNCTION__.': ';
			$err='';
			if($factory instanceof \ZedBoot\System\Error\ErrorReporterInterface) $err=$factory->getError();
			if(empty($err)) $err=get_class($factory).'::'.$def['function'].': unknown error: \\ZedBoot\\System\\Error\\ErrorReporterInterface not implemented.';
			$this->error.=$err;
		}
		if($ok && $def['singleton']) $this->singletons[$id]=$result;
		return $result;

	}
	protected function addDefinition($id,$definition)
	{
		$ok=true;
		if($ok && array_key_exists($id,$this->definitions))
		{
			$ok=false;
			$bt=debug_backtrace(false,2);
			$this->error=get_class($this).'::'.$bt[1]['function'].': '.$definition['type'].' id '.json_encode($id).' conflicts with existing '.$this->definitions[$id]['type'];
		}
		if($ok) $this->definitions[$id]=$definition;
		return $ok;
	}
	protected function extractArguments(array $args, array $dependencyChain, $preserveKeys=false)
	{
		$ok=true;
		$result=false;
		$argValues=array();
		if($ok) foreach($args as $k=>$arg)
		{
			if(is_array($arg))
			{
				//Recursively handle nested arrays
				$ok=false!==($v=$this->extractArguments($arg,$dependencyChain,true));
			}
			else if(!($ok=is_scalar($arg)))
			{
				$this->error=get_class($this).'::'.__FUNCTION__.': Encountered non-array, non-scalar argument specification.';
			}
			else $ok=$this->loadDependency($arg,$dependencyChain,$v);
			if($ok)
			{
				if($preserveKeys)
				{ $argValues[$k]=$v; }
				else $argValues[]=$v;
			}
			if(!$ok) break;
		}
		if($ok) $result=$argValues;
		return $result;
	}
}
