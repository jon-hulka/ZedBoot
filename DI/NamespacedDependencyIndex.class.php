<?php
/**
 * Class NamespacedDependencyIndex | ZedBoot/DI/NamespacedDependencyIndex.class.php
 * @license     GNU General Public License, version 3
 * @package     DI
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2018 - 2020, Jonathan Hulka
 */
namespace ZedBoot\DI;
use \ZedBoot\Error\ZBError as Err;
/**
 * Adds autoloading functionality to an instance of DependencyIndexInterface
 * When getDependencyDefinition() is called, if the dependency id has the form
 * '<namespace>:<id>', if the configuration file located at <configPath>/<namespace>.php
 * hasn't already been loaded, it will be AND
 * <namespace>: will be prepended to each _local_ (namespace not specified) id in the configuration parameters
 * Loading is handled by DependencyConfigLoader
 */
class NamespacedDependencyIndex implements \ZedBoot\DI\DependencyIndexInterface
{
	protected
		$configLoader,
		$currentNamespace=null,
		$dependencyIndex,
		$loadedConfigurations=[],
		$configPath;
	/**
	 * @param DependencyConfigLoader $configLoader for loading configuration files
	 * @param DependencyIndexInterface $dependencyIndex instance being decorated
	 * @param String $configPath file names will be resolved relative to this path
	 */
	public function __construct(
		\ZedBoot\DI\DependencyConfigLoader $configLoader,
		\ZedBoot\DI\DependencyIndexInterface $dependencyIndex,
		$configPath)
	{
		$this->configLoader=$configLoader;
		$this->dependencyIndex=$dependencyIndex;
		$this->configPath=rtrim($configPath,'/');
	}
	public function addParameters(array $parameters)
	{
		if(!empty($this->currentNamespace))
		{
			$namespaced=[];
			foreach($parameters as $id=>$param) $namespaced[$this->currentNamespace.':'.$id]=$param;
			$parameters=$namespaced;
		}
		$this->dependencyIndex->addParameters($parameters);
	}
	public function addArrayElement(string $id, string $arrayId, string $arrayKey, $ifNotExists=null)
	{
		if(!empty($this->currentNamespace))
		{
			$id=$this->currentNamespace.':'.$id;
			if(strpos($arrayId, ':') === false) $arrayId=$this->currentNamespace.':'.$arrayId;
			[$ifNotExists]=$this->namespaceArgs([$ifNotExists]);
		}
		$this->dependencyIndex->addArrayElement($id,$arrayId,$arrayKey,$ifNotExists);
	}
	public function addObjectProperty(string $id, string $objectId, string $propertyName, $ifNotExists=null)
	{
		if(!empty($this->currentNamespace))
		{
			$id=$this->currentNamespace.':'.$id;
			if(strPos($objectId, ':') === false) $objectId=$this->currentNamespace.':'.$objectId;
			[$ifNotExists]=$this->namespaceArgs([$ifNotExists]);
		}
		$this->dependencyIndex->addObjectProperty($id,$objectId,$propertyName,$ifNotExists);
	}
	public function addService(string $id,string $className,array $arguments=null,bool $singleton=true)
	{
		if(!empty($this->currentNamespace))
		{
			$id=$this->currentNamespace.':'.$id;
			if($arguments!==null) $arguments=$this->namespaceArgs($arguments);
		}
		$this->dependencyIndex->addService($id,$className,$arguments,$singleton);
	}
	public function addFactoryService(string $id,string $factoryId,string $function,array $arguments=null,bool $singleton=true)
	{
		if(!empty($this->currentNamespace))
		{
			$id=$this->currentNamespace.':'.$id;
			//If the factory is local append its namespace
			if(false===strpos($factoryId,':')) $factoryId=$this->currentNamespace.':'.$factoryId;
			if($arguments!==null) $arguments=$this->namespaceArgs($arguments);
		}
		$this->dependencyIndex->addFactoryService($id,$factoryId,$function,$arguments,$singleton);
	}
	public function addSetterInjection(string $serviceId, string $function, array $arguments)
	{
		if(!empty($this->currentNamespace))
		{
			//If the service is local append its namespace
			if(false===strpos($serviceId,':')) $serviceId=$this->currentNamespace.':'.$serviceId;
			$arguments=$this->namespaceArgs($arguments);
		}
		$this->dependencyIndex->addSetterInjection($serviceId,$function,$arguments);
	}
	public function getDependencyDefinition(string $id)
	{
		$this->checkNamespace($id);
		return $this->dependencyIndex->getDependencyDefinition($id);
	}
	public function getSetterInjections(string $serviceId)
	{
		$this->checkNamespace($serviceId);
		return $this->dependencyIndex->getSetterInjections($serviceId);
	}
	protected function checkNamespace(string $id)
	{
		$parts=explode(':',$id);
		if(count($parts)>1)
		{
			//This is a namespaced dependency
			$ns=$parts[0];
			$path=$this->configPath.'/'.$ns.'.php';
			//If the namespace isn't loaded yet, load it
			if(false===array_search($path,$this->loadedConfigurations,true))
			{
				//As parameters, services, and factory services are added,
				//$this->currentNamespace will be applied to them
				$this->currentNamespace=$ns; //!! KEEP THIS !! it affects the call to configLoader->loadConfig()
				$this->configLoader->loadConfig($this,$path);
				$this->currentNamespace=null;
				$this->loadedConfigurations[]=$path;
			}
		}
	}
	protected function namespaceArgs(array $args)
	{
		$namespaced=[];
		foreach($args as $k=>$arg)
		{
			if(is_array($arg))
			{
				//Recurse into nested arguments
				$namespaced[$k]=$this->namespaceArgs($arg);
			}
			else if(is_string($arg) && false===strpos($arg,':'))
			{
				//This is a dependency id with no namespace specified - apply current namespace
				$namespaced[$k]=$this->currentNamespace.':'.$arg;
			}
			else
			{
				//Argument already has a namespace, or isn't a dependency id
				$namespaced[$k]=$arg;
			}
		}
		return $namespaced;
	}
}
