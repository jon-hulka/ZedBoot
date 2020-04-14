<?php
$baseDir=dirname(dirname(dirname(__DIR__)));
$configDir=__DIR__.'/namespaced-config';
include $baseDir.'/ZedBoot/Bootstrap/AutoLoader.class.php';
$loader=new \ZedBoot\Bootstrap\AutoLoader();
$loader->register('ZedBoot',$baseDir.'/ZedBoot');

$configs=
[
	'ns1:ae1' => '"This"',
	'ns1:ae2' => '"This too"',
	'ns1:ae3' => '[["This too","is also","another","array too"],[["This","is","an","array"],["This again","is too","yet another","repetitive array"],null,0,1]]',
	'ns1:ae4' => 'null',
	'ns1:ae5' => '"array included"',
	'ns1:op1' => '"object"',
	'ns1:op2' => '"other object"',
	'ns1:op3' => '[{"ib":"object"},{"ib":"other object"}]',
	'ns1:op4' => 'null',
	
];
$expectedOutput=null;
$fails=[];
foreach($configs as $key=>$expectedOutput)
{
	$configLoader=new \ZedBoot\DI\DependencyConfigLoader();
	//$dependencyIndex finds and loads namespaced dependency configuration files as needed by $dependencyLoader
	$dependencyIndex=new \ZedBoot\DI\NamespacedDependencyIndex($configLoader, new \ZedBoot\DI\SimpleDependencyIndex(),$configDir);
	$dependencyLoader=new \ZedBoot\DI\SimpleDependencyLoader($dependencyIndex);
	try
	{
		$output=json_encode($dependencyLoader->getDependency($key));
		if($output===$expectedOutput)
		{
			echo $key.' output: '.$output.'
OK
';
		}
		else
		{
			$fails[]=$key;
			echo 'Failed on '.$key.':
expected: '.$expectedOutput.'
got:      '.$output.'
';
		}
	}
	catch(\Exception $e)
	{
		$fails[]=$key;
			echo 'Failed on '.$key.':
expected:  '.$expectedOutput.'
got error: '.$e->getMessage().'
';
	}
}

if(count($fails)===0)
{
	echo 'All OK
';
}
else echo 'These tests failed:
 - '.implode('
 - ',$fails).'
';
echo 'TO DO: add tests for services, factory services and setter injections
';
