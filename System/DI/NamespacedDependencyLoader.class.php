<?php
namespace ZedBoot\System\DI;
use \ZedBoot\System\Error\ZBError as Err;
/**
 * Adds autoloading functionality to an instance of DependencyLoaderInterface
 * When getDependency() is called, if the dependency id has the form
 * '<namespace>:<id>', the configuration file located at <configPath>/<namespace>.php
 * will be loaded (if it hasn't already been)
 * This implementation of DependencyLoaderInterface extends DependencyConfigLoader
 */
class NamespacedDependencyLoader extends \ZedBoot\System\DI\DependencyConfigLoader implements \ZedBoot\System\DI\DependencyLoaderInterface
{
	protected
		$dependencyLoader,
		$configLoader,
		$loadedConfigurations=array(),
		$configPath;
	/**
	 * @param DependencyLoader $dependencyLoader
	 * @param ConfigLoader $configLoader
	 * @param String $configPath file names will be resolved relative to this path
	 */
	public function __construct(
		\ZedBoot\System\DI\DependencyLoaderInterface $dependencyLoader,
		$configPath)
	{
		$this->dependencyLoader=$dependencyLoader;
		$this->configPath=rtrim($configPath,'/');
		parent::__construct($dependencyLoader);
	}
	public function addParameters(array $parameters)
	{
		$this->dependencyLoader->addParameters($parameters);
	}
	public function addService($id,$className,array $arguments=null,$singleton=true)
	{
		$this->dependencyLoader->addService($id,$className,$arguments,$singleton);
	}
	public function addFactoryService($id,$factoryId,$function,array $arguments=null,$singleton=true)
	{
		$this->dependencyLoader->addFactoryService($id,$factoryId,$function,$arguments,$singleton);
	}
	public function getDependency($id,$classType=null)
	{
//Any unresolved namespaces might be buried deeper in the dependency chain,
//so we must listen for UndefinedDependencyException and handle it accordingly
//To do: find out if doing this via try/catch is going to be a performance problem
//To do: test on cases where the dependency chain has multiple unresolved namespaces
//(may not be necessary to build a test, this will likely come up in development)
		$result=null;
		try
		{
			$result=$this->dependencyLoader->getDependency($id,$classType);
		}
		catch(\ZedBoot\System\DI\UndefinedDependencyException $e)
		{
			$undefinedId=$e->getDependencyId();
			$parts=explode(':',$undefinedId);
			if(count($parts)>1)
			{
				//This is a namespaced dependency
				$ns=$parts[0];
				$path=$this->configPath.'/'.$ns.'.php';
				if(false===array_search($path,$this->loadedConfigurations,true))
				{
					//The namespace isn't loaded yet, load it
					$this->loadConfig($path);
					//Try again from the top
					//This could happen multiple times if there are multiple namespaces in the dependency chain
					$result=$this->getDependency($id,$classType);
				}
				//Namespace is already loaded, this is an error
				else throw $e;
			}
		}
		return $result;
	}
	public function loadConfig($path,array $configParameters=null)
	{
		parent::loadConfig($path,$configParameters);
		//Remember loaded namespaces so loadNamespace won't try again
		$this->loadedConfigurations[]=$path;
	}
}
