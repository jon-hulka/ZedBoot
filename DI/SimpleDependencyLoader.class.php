<?php

/**
 * Class SimpleDependencyLoader | ZedBoot/DI/SimpleDependencyLoader.class.php
 * @license     GNU General Public License, version 3
 * @package     DI
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2017 - 2021, Jonathan Hulka
 */

/**
 * Dependency loader implementation
 * Provides a simple implementation of DependencyLoaderInterface
 */
namespace ZedBoot\DI;
use \ZedBoot\Error\ZBError as Err;
class SimpleDependencyLoader implements \ZedBoot\DI\DependencyLoaderInterface
{
	protected
		$dependencyIndex=null,
		$singletons=[];
	public function __construct(\ZedBoot\DI\DependencyIndexInterface $dependencyIndex)
	{
		$this->dependencyIndex=$dependencyIndex;
	}
	public function getDependency(string $id, string $type=null)
	{
		$result = $this->loadDependency($id, []);
		if(!empty($type))
		{
			$t = gettype($result);
			if($t !== $type)
			{
				if($t === 'object')
				{
					if(!($result instanceof $type)) throw new Err('Getting '.$id.': Expected '.$type.', got '.get_class($result));
				}
				else throw new Err('Getting '.$id.': Expected '.$type.', got '.$t);
			}
		}
		return $result;
	}
	protected function loadDependency(string $id, array $dependencyChain)
	{
		$result = false;
		try
		{
			if(array_key_exists($id, $this->singletons))
			{
				//Dependency has already been loaded
				$result = $this->singletons[$id];
			}
			else
			{
				try
				{
					$def = $this->dependencyIndex->getDependencyDefinition($id);
				}
				catch(\Exception $e)
				{
					throw new Err('Loading dependency definition: '.$e->getMessage().': Dependency chain: '.implode(' > ', $dependencyChain).' > '.$id);
				}
				switch($def['type'])
				{
					case 'parameter':
						$result = $def['value'];
						break;
					case 'alias':
						$result = $this->loadAlias($id, $def, $dependencyChain);
						break;
					case 'array element':
						$result = $this->loadArrayElement($id, $def, $dependencyChain);
						break;
					case 'object property':
						$result = $this->loadObjectProperty($id, $def, $dependencyChain);
						break;
					case 'service':
						$result = $this->loadService($id, $def, $dependencyChain);
						break;
					case 'factory service':
						$result = $this->loadFactoryService($id, $def, $dependencyChain);
						break;
				}
			}
		}
		catch(\Exception $e)
		{
			if($e instanceof \Zedboot\DI\DependencyLoaderException)
			{
				throw $e;
			}
			else
			{
				$chain = $dependencyChain;
				$chain[] = $id;
				throw new \ZedBoot\DI\DependencyLoaderException('Error loading '.$id, 0, $e, $chain);
			}
		}
		return $result;
	}

	protected function loadAlias(string $id, array $def, array $dependencyChain)
	{
		//Make sure this alias is not its own ancestor
		if(false !== (array_search($id, $dependencyChain, true)))
			throw new Err('Circular dependency: '.implode(' > ', $dependencyChain).' > '.$id);
		$dependencyChain[] = $id;
		return $this->loadDependency($def['alias_of_id'], $dependencyChain);
	}

	/**
	 * Array elements are loaded fresh every time because the sources could be loaded by non-Singleton factories
	 */
	protected function loadArrayElement(string $id, array $def, array $dependencyChain)
	{
		$result = null;
		$ne = $def['if_not_exists'];
		//Make sure this array is not its own ancestor
		if(false !== (array_search($id, $dependencyChain, true)))
			throw new Err('Circular dependency: '.implode(' > ', $dependencyChain).' > '.$id);
		$dependencyChain[] = $id;
		$arr = $this->loadDependency($def['array_id'], $dependencyChain);
		if(!is_array($arr)) throw new Err($id.' arrayId: Expected '.$def['array_id'].' to be array, got '.gettype($arr));
		if(array_key_exists($def['key'], $arr))
		{
			$result = $arr[$def['key']];
		}
		else if(is_string($ne))
		{
			$result = $this->loadDependency($ne, $dependencyChain);
		}
		else if(is_array($ne))
		{
			$result = $this->extractArguments($ne, $dependencyChain, true, 'array element');
		}
		else
		{
			$result = $ne;
		}
		return $result;
	}

	/**
	 * Object properties are loaded fresh every time because they could rely on non-Singleton sources
	 */
	protected function loadObjectProperty(string $id, array $def, array $dependencyChain)
	{
		$result = null;
		$ne = $def['if_not_exists'];
		//Make sure this object is not its own ancestor
		if(false !== (array_search($id, $dependencyChain, true)))
			throw new Err('Circular dependency: '.implode(' > ', $dependencyChain).' > '.$id);
		$dependencyChain[] = $id;
		$obj = $this->loadDependency($def['object_id'], $dependencyChain);
		if(!is_object($obj)) throw new Err($id.' objectId: Expected '.$def['object_id'].' to be object, got '.gettype($obj));
		$prop = $def['property'];
		if(property_exists($obj, $prop))
		{
			$result = $obj->$prop;
		}
		else if(is_string($ne))
		{
			$result = $this->loadDependency($ne, $dependencyChain);
		}
		else if(is_array($ne))
		{
			$result = $this->extractArguments($ne, $dependencyChain, true, 'array element');
		}
		else
		{
			$result = $ne;
		}
		return $result;
	}

	/**
	 * @param $id
	 * @param array $dependencyChain dependencies for the current branch of the dependency tree. Used to detect circular dependencies.
	 */
	protected function loadService(string $id, array $def, array $dependencyChain)
	{
		$result = false;
		$argValues = [];
		//Make sure this service is not its own ancestor
		if(false !== (array_search($id, $dependencyChain, true)))
			throw new Err('Circular dependency: '.implode(' > ', $dependencyChain).' > '.$id);
		//Retrieve arguments needed by this service's constructor
		$dependencyChain[] = $id;
		$argValues = $this->extractArguments($def['args'], $dependencyChain);
		//Create a new instance
		$cn = $def['class_name'];
		try
		{
			$reflect = new \ReflectionClass($cn);
			$result = $reflect->newInstanceArgs($argValues);
		}
		catch(\Exception $e)
		{
			throw new Err('Loading dependency (instance of '.$cn.'): '.$e->getMessage().': Dependency chain: '.implode(' > ', $dependencyChain));
		}
		if(is_object($result)) $this->checkSetterInjection($result, $id);
		if($def['singleton']) $this->singletons[$id] = $result;
		return $result;
	}

	protected function loadFactoryService(string $id, array $def, array $dependencyChain)
	{
		$result = false;
		$factory = null;
		$argValues = [];
		//Make sure this dependency is not its own ancestor
		if(false !== (array_search($id, $dependencyChain, true)))
			throw new Err('Circular dependency: '.implode(' > ', $dependencyChain).' > '.$id);
		$dependencyChain[] = $id;
		$factoryId = $def['factory_id'];
		if(false !== (array_search($factoryId, $dependencyChain, true)))
			throw new Err('Circular dependency: '.implode(' > ', $dependencyChain).' > '.$factoryId);
		$factory = $this->loadDependency($factoryId, $dependencyChain);
		if(!is_object($factory)) throw new Err('Expected factory '.json_encode($factoryId).' to be an object. Got '.gettype($factory).': Dependency chain: '.implode(' > ', $dependencyChain));
		$argValues = $this->extractArguments($def['args'], $dependencyChain);
		try
		{
			$result = ([$factory, $def['function']])(...$argValues);
		}
		catch(\Exception $e)
		{
			throw new Err('Running factory function: '.$id.': '.$factoryId.'::'.$def['function'].'(): '.$e->getMessage().': Dependency chain: '.implode(' > ', $dependencyChain));
		}
		if(is_object($result)) $this->checkSetterInjection($result, $id);
		if($def['singleton']) $this->singletons[$id] = $result;
		return $result;

	}

	protected function checkSetterInjection($service, string $id)
	{
		$setterInjections = $this->dependencyIndex->getSetterInjections($id);
		foreach($setterInjections as $def)
		{
			$argValues = $this->extractArguments($def['args'], []);
			([$service, $def['function']])(...$argValues);
		}
	}

	protected function extractArguments(array $args, array $dependencyChain, bool $preserveKeys=false, $entityName='argument')
	{
		$argValues = [];
		$v = null;
		foreach($args as $k => $arg)
		{
			if(is_array($arg))
			{
				//Recursively handle nested arrays
				$v = $this->extractArguments($arg, $dependencyChain, true, 'array element');
			}
			else if(is_string($arg))
			{
				$v = $this->loadDependency($arg,$dependencyChain);
			}
			else if(is_scalar($arg) || is_null($arg))
			{
				$v = $arg;
			}
			else
			{
				throw new Err('Expected '.$entityName.' to be one of: dependency id (string), array, null, or scalar constant, got '.gettype($arg));
			}
			if($preserveKeys)
			{
				$argValues[$k] = $v;
			}
			else $argValues[] = $v;
		}
		return $argValues;
	}
}
