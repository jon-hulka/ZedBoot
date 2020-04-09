<?php
include '../../Bootstrap/AutoLoader.class.php';
$loader=new \ZedBoot\Bootstrap\AutoLoader();
$loader->register('ZedBoot',dirname(dirname(__DIR__)));
$configLoader=new \ZedBoot\DI\DependencyConfigLoader();
$index=new \ZedBoot\DI\SimpleDependencyIndex();
$dependencyLoader=new \ZedBoot\DI\SimpleDependencyLoader($index);

$configLoader->loadConfig($index,__DIR__.'/config.php');
echo 'spanishGreeter->greet(name):
';
echo $dependencyLoader->getDependency('spanishGreeter')->greet($dependencyLoader->getDependency('name')).'
';
echo 'frenchGreeter->greet(name):
';
echo $dependencyLoader->getDependency('frenchGreeter')->greet($dependencyLoader->getDependency('name')).'
';
echo 'germanGreeter->greet(name):
';
echo $dependencyLoader->getDependency('germanGreeter')->greet($dependencyLoader->getDependency('name')).'
';
echo 'englishGreeter->greet(name):
';
echo $dependencyLoader->getDependency('englishGreeter')->greet($dependencyLoader->getDependency('name')).'
';
echo 'otherGreeter->greet(name):
';
echo $dependencyLoader->getDependency('otherGreeter')->greet($dependencyLoader->getDependency('name')).'
';
