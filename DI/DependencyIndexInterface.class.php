<?php
/**
 * Interface DependencyIndexInterface | ZedBoot/DI/DependencyIndexInterface.class.php
 * @license     GNU General Public License, version 3
 * @package     DI
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2018, Jonathan Hulka
 */

/**
 * Dependency index
 * Implementations store and retrieve dependency definitions by id
 */
namespace ZedBoot\DI;
interface DependencyIndexInterface
{
	/**
	 * @param Array $parameters keys cannot conflict with existing ids
	 */
	public function addParameters(array $parameters);
	/**
	 * @param string $id unique identifier for the service, cannot conflict with existing service or parameter ids
	 * @param mixed $arguments array of parameter and service ids to pass into constructor, null for none, nested arrays are allowed, boolean, null, and numeric treated as constants
	 * @param boolean $singleton true to use a single instance, false for new instance every time
	 */
	public function addService($id,$className,array $arguments=null,$singleton=true);
	/**
	 * @param string $id dependency id
	 * @param string $factoryId dependency id of factory class
	 * @param mixed $arguments array of dependency ids to pass to factory function, null for none, nested arrays are allowed
	 * @param boolean $singleton true to use a single instance, false for new instance every time
	 */
	public function addFactoryService($id,$factoryId,$function,array $arguments=null,$singleton=true);
	/**
	 * Throws an exception if dependency cannot be found
	 * @param $id dependency id
	 * @return array one of: array('type'=>'parameter','value'=><value>), array('type'=>'service','class_name'=><class name>,'args'=>array(...),'singleton'=>boolean), or array('type'=>'factory service', 'factory_id'=><factory id>, 'function'=><function name>, 'args'=>array(...), 'singleton'=>boolean)
	 */
	public function getDependencyDefinition($id);
}
