<?php
/**
 * Class NamespacedDependencyIndex | ZedBoot/System/DI/NamespacedDependencyIndex.class.php
 * @license     GNU General Public License, version 3
 * @package     System
 * @subpackage  DI
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2018, Jonathan Hulka
 */
namespace ZedBoot\System\DI;
use \ZedBoot\System\Error\ZBError as Err;
/**
 * Adds autoloading functionality to an instance of DependencyIndexInterface
 * When getDependencyDefinition() is called, if the dependency id has the form
 * '<namespace>:<id>', if the configuration file located at <configPath>/<namespace>.php
 * hasn't already been loaded, it will be AND
 * <namespace>: will be prepended to each _local_ (namespace not specified) id in the configuration parameters
 * Loading is handled by DependencyConfigLoader
 */
class NamespacedDependencyIndex implements \ZedBoot\System\DI\DependencyIndexInterface
{
	protected
		$configLoader,
		$currentNamespace=null,
		$dependencyIndex,
		$loadedConfigurations=array(),
		$configPath;
	/**
	 * @param DependencyConfigLoader $configLoader for loading configuration files
	 * @param DependencyIndexInterface $dependencyIndex instance being decorated
	 * @param String $configPath file names will be resolved relative to this path
	 */
	public function __construct(
		\ZedBoot\System\DI\DependencyConfigLoader $configLoader,
		\ZedBoot\System\DI\DependencyIndexInterface $dependencyIndex,
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
			$namespaced=array();
			foreach($parameters as $id=>$param) $namespaced[$this->currentNamespace.':'.$id]=$param;
			$parameters=$namespaced;
		}
		$this->dependencyIndex->addParameters($parameters);
	}
	public function addService($id,$className,array $arguments=null,$singleton=true)
	{
		if(!empty($this->currentNamespace))
		{
			$id=$this->currentNamespace.':'.$id;
			if($arguments!==null) $arguments=$this->namespaceArgs($arguments);
		}
		$this->dependencyIndex->addService($id,$className,$arguments,$singleton);
	}
	public function addFactoryService($id,$factoryId,$function,array $arguments=null,$singleton=true)
	{
		if(!empty($this->currentNamespace))
		{
			$id=$this->currentNamespace.':'.$id;
			if(false===strpos($factoryId,':')) $factoryId=$this->currentNamespace.':'.$factoryId;
			if($arguments!==null) $arguments=$this->namespaceArgs($arguments);
		}
		$this->dependencyIndex->addFactoryService($id,$factoryId,$function,$arguments,$singleton);
	}
	public function getDependencyDefinition($id)
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
				$this->currentNamespace=$ns;
				$this->configLoader->loadConfig($this,$path);
				$this->currentNamespace=null;
				$this->loadedConfigurations[]=$path;
			}
		}
		return $this->dependencyIndex->getDependencyDefinition($id);
	}
	protected function namespaceArgs(array $args)
	{
		$namespaced=array();
		foreach($args as $k=>$arg)
		{
			if(is_array($arg))
			{
				//Recurse into nested arguments
				$namespaced[$k]=$this->namespaceArgs($arg);
			}
			else if(false===strpos($arg,':'))
			{
				//No namespace specified - this is a local argument - apply current namespace
				$namespaced[$k]=$this->currentNamespace.':'.$arg;
			}
			else
				//Argument already specifies a namespace
				$namespaced[$k]=$arg;
		}
		return $namespaced;
	}
}
