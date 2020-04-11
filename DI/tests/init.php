<?php
/**
 * To be run from the command line
 */
include '../../Bootstrap/AutoLoader.class.php';
$loader=new \ZedBoot\Bootstrap\AutoLoader();
$loader->register('ZedBoot',dirname(dirname(__DIR__)));

$configLoader=null;
$index=null;
$dependencyLoader=null;

$key=null;
$cf=null;
$expectedError=null;
$expectedOutput=null;
$fileContents=null;
$sectionName=null;
$sectionData=null;
$testPath=__DIR__.'/tmp';
$fails=[];
if(count($argv)===2)
{
	@mkdir($testPath);
	include(__DIR__.'/config/'.$argv[1].'.php');
	foreach($configs as $key=>$cf)
	{
		//$key is the dependency key to load
		//$cf has the dependency configuration
		$configLoader=new \ZedBoot\DI\DependencyConfigLoader();
		$index=new \ZedBoot\DI\SimpleDependencyIndex();
		$dependencyLoader=new \ZedBoot\DI\SimpleDependencyLoader($index);
		$expectedError=null;
		$expectedOutput=null;
		$fileContents='<?php
';
		foreach($cf as $sectionName => $data)
		{
			switch($sectionName)
			{
				case 'error':
					$expectedError=$data;
					break;
				case 'output':
					$expectedOutput=$data;
					break;
				default:
					$fileContents.='$'.$sectionName.' =
'.var_export($data,true).';
';
					break;
			}
		}
		if(!empty($postProcess)) $fileContents=$postProcess($fileContents);
		file_put_contents($testPath.'/test.php',$fileContents);

		try
		{
			$configLoader->loadConfig($index,$testPath.'/test.php');
			$output=json_encode($dependencyLoader->getDependency($key));
			if($expectedError!==null)
			{
				$fails[]=$key;
				echo 'Failed on '.$key.':
expected error: '.$expectedError.'
got output:     '.$output;
				break;
			}
			else if($output!==$expectedOutput)
			{
				$fails[]=$key;
				echo 'Failed on '.$key.':
expected: '.$expectedOutput.'
got:      '.$output;
			}
			else echo $key.' output: '.$output.'
OK';
		}
		catch(Exception $e)
		{
			$msg=$e->getMessage();
			if($expectedOutput!==null)
			{
				$fails[]=$key;
				echo 'Failed on '.$key.':
expected:  '.$expectedOutput.'
got error: '.$msg;
			}
			else if(strpos($msg,$expectedError)===false)
			{
				$fails[]=$key;
				echo 'Failed on '.$key.':
expected error: '.$expectedError.'
got error:      '.$msg;
			}
			else
			{
				echo $key.' error: '.$msg.'
OK';
			}
		}
		echo '

';
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
	unlink($testPath.'/test.php');
	rmdir($testPath);
}
else echo 'usage: php init.php config_name
example: php init.php cycles
check the config directory for other options
';
