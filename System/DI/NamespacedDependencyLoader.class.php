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
		$parts=explode(':',$id);
		if(count($parts)>1) $this->loadNamespace($parts[0]);
		return $this->dependencyLoader->getDependency($id,$classType);
	}
	public function loadConfig($path,array $configParameters=null)
	{
		parent::loadConfig($path,$configParameters);
		//Remember loaded namespaces so loadNamespace won't try again
		$this->loadedConfigurations[]=$path;
	}
	protected function loadNamespace($namespace)
	{
		$path=$this->configPath.'/'.$namespace.'.php';
		if(false===array_search($path,$this->loadedConfigurations,true)) $this->loadConfig($path);
	}
}
