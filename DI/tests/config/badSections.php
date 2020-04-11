<?php
//Converts strings '{{"prop" => "value"}}' to objects
$postProcess=function(string $contents)
{
	return str_replace('}}\'',']',str_replace('\'{{','(object)[',$contents));
};
$configs =
[
	'badParameters' =>
	[
		'error' => '$parameters is not an array in config file ',
		'parameters' => ''
	],
	'badArrayElements' =>
	[
		'error' => '$arrayElements is not an array in config file ',
		'arrayElements' => false
	],
	'badObjectProperties' =>
	[
		'error' => '$objectProperties is not an array in config file ',
		'objectProperties' => '{{}}'
	],
	'badServices' =>
	[
		'error' => '$services is not an array in config file ',
		'services' => 1
	],
	'badFactoryServices' =>
	[
		'error' => '$factoryServices is not an array in config file ',
		'factoryServices' => 4.5
	],
	'badIncludes' =>
	[
		'error' => '$includes is not an array in config file ',
		'includes' => false
	],
	'badSetterInjections' =>
	[
		'error' => '$setterInjections is not an array in config file ',
		'setterInjections' => false
	],
/*
	'' =>
	[
		'parameters' =>
		[
		],
		'arrayValues' =>
		[
		],
		'objectProperties' =>
		[
		],
		'services' =>
		[
		],
		'factoryServices' =>
		[
		],
		'includes' =>
		[
		],
		'setterInjections' =>
		[
		]
	],
*/
];
