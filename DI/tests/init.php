<?php
include '../../Bootstrap/AutoLoader.class.php';
$loader=new \ZedBoot\Bootstrap\AutoLoader();
$loader->register('ZedBoot',dirname(dirname(__DIR__)));
$configLoader=new \ZedBoot\DI\DependencyConfigLoader();
$index=new \ZedBoot\DI\SimpleDependencyIndex();
$dependencyLoader=new \ZedBoot\DI\SimpleDependencyLoader($index);

if(count($argv)>2)
{
	//Some config files use this to set up the dependency tree
	$dependency=$argv[2];
	$configLoader->loadConfig($index,__DIR__.'/config/'.$argv[1].'.php');
	$dep=$dependencyLoader->getDependency($argv[2]);
	echo json_encode($dep).'
';
}
else
{
	echo 'usage: php init.php config_name dependency_id
example: php init.php cycles svc.cycle';
}
