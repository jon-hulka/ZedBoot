<?php
/**
 * Interface DependencyIndexInterface | ZedBoot/DI/DependencyIndexInterface.class.php
 * @license     GNU General Public License, version 3
 * @package     DI
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2018 - 2021, Jonathan Hulka
 */

/**
 * Dependency index
 * Implementations store and retrieve dependency definitions by id
 */
namespace ZedBoot\DI;
interface DependencyIndexInterface
{
	/**
	 * @param Array $parameters keys cannot conflict with existing dependency ids
	 */
	public function addParameters(array $parameters);
	/**
	 * @param string $id unique dependency id for the alias, cannot conflict with existing dependency ids
	 * @param string $indexOfId dependency id of the aliased dependency
	 */
	public function addAlias(string $id, string $indexOfId);
	/**
	 * @param string $id unique dependency id for the parameter, cannot conflict with existing dependency ids
	 * @param string $arrayId dependency id of the array
	 * @param string $arrayKey array key
	 * @param mixed $ifNotExists string will be treated as a dependency key, other scalar value as literal
	 */
	public function addArrayElement(string $id, string $arrayId, string $arrayKey, $ifNotExists=null);
	/**
	 * @param string $id unique dependency id for the parameter, cannot conflict with existing dependency ids
	 * @param string $objectId dependency id of the object
	 * @param string $propertyName property name
	 */
	public function addObjectProperty(string $id, string $objectId, string $propertyName, $ifNotExists=null);
	/**
	 * @param string $id unique dependency id for the service, cannot conflict with existing dependency ids
	 * @param mixed $arguments array of parameter and service ids to pass into constructor, null for none, nested arrays are allowed, boolean, null, and numeric treated as constants
	 * @param boolean $singleton true to use a single instance, false for new instance every time
	 */
	public function addService(string $id,string $className,array $arguments=null,bool $singleton=true);
	/**
	 * @param string $id unique dependency id for the service, cannot conflict with existing dependency ids
	 * @param string $factoryId dependency id of factory class
	 * @param mixed $arguments array of dependency ids to pass to factory function, null for none, nested arrays are allowed, boolean, null, and numeric treated as constants
	 * @param boolean $singleton true to use a single instance, false for new instance every time
	 */
	public function addFactoryService(string $id,string $factoryId,string $function,array $arguments=null,bool $singleton=true);
	/**
	 * Defines a function with parameters to be called when the dependency resolves
	 * @param String $serviceId dependency id to run setter on
	 * @param String $function setter function name
	 * @param Array $arguments dependency ids to pass to setter function, nested arrays are allowed, boolean, null, and numeric treated as constants
	 */
	public function addSetterInjection(string $serviceId,string $function, array $arguments);
	/**
	 * Throws an exception if dependency cannot be found
	 * @param $id dependency id
	 * @return array one of:
	 * ['type'=>'parameter','value'=><value>],
	 * ['type'=>'array value', 'array_id'=><dependency id>, 'key'=><array key>, 'if_not_exists'=><string dependency id or scalar literal>],
	 * ['type' => 'object property', 'object_id' => <dependency id>,'property' => <string property name>],
	 * ['type'=>'service','class_name'=><class name>,'args'=>[...],'singleton'=>boolean],
	 * or ['type'=>'factory service', 'factory_id'=><factory id>, 'function'=><function name>, 'args'=>[...], 'singleton'=>boolean]
	 */
	public function getDependencyDefinition(string $id);
	/**
	 * @param $id dependency id
	 * @return Array [ ['function'=><function name>, 'args'=>[...] ] , ...]
	 */
	public function getSetterInjections(string $serviceId);
}
