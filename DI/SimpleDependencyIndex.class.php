<?php
/**
 * Class SimpleDependencyIndex | ZedBoot/DI/SimpleDependencyIndex.class.php
 * @license     GNU General Public License, version 3
 * @package     DI
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2018 - 2020, Jonathan Hulka
 */
namespace ZedBoot\DI;
use \ZedBoot\Error\ZBError as Err;
class SimpleDependencyIndex implements \ZedBoot\DI\DependencyIndexInterface
{
	protected
		$definitions=[],
		$setterInjections=[];
	public function addParameters(array $parameters)
	{
		foreach($parameters as $id=>$def) $this->addDefinition($id,[
			'type'=>'parameter',
			'value'=>$def
		]);
	}
	public function addArrayValue(string $id, string $arrayId, string $arrayKey, $ifNotExists=null)
	{
		$this->addDefinition
		(
			$id,
			[
				'type'=>'array value',
				'array_id'=>$arrayId,
				'key'=>$arrayKey,
				'if_not_exists'=>$ifNotExists
			]
		);
	}
	public function addObjectProperty(string $id, string $objectId, string $propertyName, $ifNotExists=null)
	{
		$this->addDefinition
		(
			$id,
			[
				'type' => 'object property',
				'object_id' => $objectId,
				'property' => $propertyName,
				'if_not_exists' => $ifNotExists
			]
		);
	}
	public function addService(string $id,string $className,array $arguments=null,bool $singleton=true)
	{
		if(empty($arguments)) $arguments=[];
		$this->addDefinition($id,[
			'type'=>'service',
			'class_name'=>$className,
			'args'=>$arguments,
			'singleton'=>$singleton
		]);
	}
	public function addFactoryService(string $id,string $factoryId,string $function,array $arguments=null,bool $singleton=true)
	{
		if(empty($arguments)) $arguments=[];
		$this->addDefinition($id,[
			'type'=>'factory service',
			'factory_id'=>$factoryId,
			'function'=>$function,
			'args'=>$arguments,
			'singleton'=>$singleton
		]);
	}
	public function addSetterInjection(string $serviceId, string $function, array $arguments)
	{
		if(!array_key_exists($serviceId,$this->setterInjections)) $this->setterInjections[$serviceId]=[];
		$this->setterInjections[$serviceId][]=['function'=>$function,'args'=>$arguments];
	}
	public function getDependencyDefinition(string $id)
	{
		if(!array_key_exists($id,$this->definitions))
			throw new Err('Attempt to get undefined dependency: '.json_encode($id).'.');
		return $this->definitions[$id];
	}
	public function getSetterInjections(string $serviceId)
	{
		$result=null;
		if(array_key_exists($serviceId,$this->setterInjections))
		{
			$result=$this->setterInjections[$serviceId];
		}
		else $result=[];
		return $result;
	}
	protected function addDefinition($id,$definition)
	{
		if(array_key_exists($id,$this->definitions)) throw new Err($definition['type'].' id '.json_encode($id).' conflicts with existing '.$this->definitions[$id]['type']);
		$this->definitions[$id]=$definition;
	}
}
