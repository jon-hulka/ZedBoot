<?php
global $dependency;

$configs =
[
	'badParameters' =>
	[
		'parameters' => ''
	],
	'badArrayValues' =>
	[
		'arrayValues' => false
	],
	'badObjectProperties' =>
	[
		'objectProperties' => (object) []
	],
	'badServices' =>
	[
		'services' => 1
	],
	'badFactoryServices' =>
	[
		'factoryServices' => 4.5
	],
	'badIncludes' =>
	[
		'includes' => false
	],
	'badSetterInjections' =>
	[
		'setterInjections' => false
	],
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
];
if(array_key_exists($dependency,$configs)) extract($configs[$dependency]);
