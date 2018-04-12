<?php
/**
 * Interface DependencyLoaderInterface | ZedBoot/System/DI/DependencyLoaderInterface.class.php
 * @license     GNU General Public License, version 3
 * @package     System
 * @subpackage  DI
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2017, Jonathan Hulka
 */

/**
 * Dependency loader
 * Defines the interface for loading services and parameters
 */
namespace ZedBoot\System\DI;
interface DependencyLoaderInterface extends \ZedBoot\System\Error\ErrorReporterInterface
{
	/**
	 * @param Array $parameters keys cannot conflict with existing ids
	 * @return boolean true on success, false on error
	 */
	public function addParameters(array $parameters);
	/**
	 * @param string $id unique identifier for the service, cannot conflict with existing service or parameter ids
	 * @param mixed $arguments array of parameter and service ids to pass into constructor, null for none, nested arrays are allowed
	 * @param boolean $singleton true to use a single instance, false for new instance every time
	 * @return boolean true on success, false on error
	 */
	public function addService($id,$className,array $arguments=null,$singleton=true);
	/**
	 * @param string $id dependency id
	 * @param string $factoryId dependency id of factory class
	 * @param mixed $arguments array of dependency ids to pass to factory function, null for none
	 * @param boolean $singleton true to use a single instance, false for new instance every time
	 */
	public function addFactoryService($id,$factoryId,$function,array $arguments=null,$singleton=true);
	/**
	 * The result value is not returned because false (indicating error) is a possible result
	 * @param string $id parameter id as specified in addParameters array
	 * @param mixed $result parameter value, should be set regardless of whether $classType matches
	 * @param mixed $classType expected result type or null
	 * @return boolean error status
	 */
	public function getDependency($id,&$result,$classType=null);
}
