<?php
/**
 * Class SimpleDependencyIndex | ZedBoot/System/DI/SimpleDependencyIndex.class.php
 * @license     GNU General Public License, version 3
 * @package     System
 * @subpackage  DI
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2018, Jonathan Hulka
 */
namespace ZedBoot\System\DI;
use \ZedBoot\System\Error\ZBError as Err;
class SimpleDependencyIndex implements \ZedBoot\System\DI\DependencyIndexInterface
{
	protected
		$definitions=array();
	public function addParameters(array $parameters)
	{
		foreach($parameters as $id=>$def) $this->addDefinition($id,array(
			'type'=>'parameter',
			'value'=>$def
		));
	}
	public function addService($id,$className,array $arguments=null,$singleton=true)
	{
		if(empty($arguments)) $arguments=array();
		$this->addDefinition($id,array(
			'type'=>'service',
			'class_name'=>$className,
			'args'=>$arguments,
			'singleton'=>$singleton
		));
	}
	public function addFactoryService($id,$factoryId,$function,array $arguments=null,$singleton=true)
	{
		if(empty($arguments)) $arguments=array();
		$this->addDefinition($id,array(
			'type'=>'factory service',
			'factory_id'=>$factoryId,
			'function'=>$function,
			'args'=>$arguments,
			'singleton'=>$singleton
		));
	}
	public function getDependencyDefinition($id)
	{
		if(!array_key_exists($id,$this->definitions))
			throw new Err('Attempt to get undefined dependency: '.json_encode($id).'.',$id);
		return $this->definitions[$id];
	}
	protected function addDefinition($id,$definition)
	{
		if(array_key_exists($id,$this->definitions)) throw new Err($definition['type'].' id '.json_encode($id).' conflicts with existing '.$this->definitions[$id]['type']);
		$this->definitions[$id]=$definition;
	}
}
