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
 * '<namespace>:<id>', the configuration file located at <configPath>/<namespace>.php
 * will be loaded (if it hasn't already been)
 * Loading is handled by DependencyConfigLoader
 */
class NamespacedDependencyIndex implements \ZedBoot\System\DI\DependencyIndexInterface
{
	protected
		$configLoader,
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
		$this->dependencyIndex->addParameters($parameters);
	}
	public function addService($id,$className,array $arguments=null,$singleton=true)
	{
		$this->dependencyIndex->addService($id,$className,$arguments,$singleton);
	}
	public function addFactoryService($id,$factoryId,$function,array $arguments=null,$singleton=true)
	{
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
				$this->configLoader->loadConfig($this,$path);
				$this->loadedConfigurations[]=$path;
			}
		}
		return $this->dependencyIndex->getDependencyDefinition($id);
	}
}
