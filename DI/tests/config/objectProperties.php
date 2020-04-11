<?php
//Converts strings '{{"prop" => "value"}}' to objects
$postProcess=function(string $contents)
{
	return str_replace('}}\'',']',str_replace('\'{{','(object)[',$contents));
};
$configs=
[
	'tooMany' =>
	[
		'error' => 'objectProperties: "tooMany": must have no more than 3 parameters ( objectId, propertyName, optional ifNotExists )',
		'objectProperties' =>
		[
			'tooMany' => [1,2,3,4]
		],
	],
	'tooFew' =>
	[
		'error' => 'objectProperties: "tooFew": must have at least 2 parameters ( objectId, propertyName, optional ifNotExists )',
		'objectProperties' =>
		[
			'tooFew' => [1]
		],
	],
	'badId' =>
	[
		'error' => 'objectProperties: "badId": 1st parameter (objectId) expected string, got array',
		'objectProperties' =>
		[
			'badId' => [[],'key']
		],
	],
	'badProp1' =>
	[
		'error' => 'objectProperties: "badProp1": 2nd parameter (propertyName) expected string, got NULL',
		'objectProperties' =>
		[
			'badProp1' => ['id',null]
		],
	],
	'badProp2' =>
	[
		'error' => 'objectProperties: "badProp2": 2nd parameter (propertyName) expected string, got integer',
		'objectProperties' =>
		[
			'badProp2' => ['id',2]
		],
	],
	'notObj' =>
	[
		'error' => 'Error loading notObj: dependency chain: notObj: notObj objectId: Expected arr to be object, got array',
		'parameters' =>
		[
			'arr' => ['test']
		],
		'objectProperties' =>
		[
			'notObj' => ['arr','prop']
		],
	],
	'badNE1' =>
	[
		//Invalid ifNotExists type: object
		'error' => 'objectProperties: "badNE1": 3rd parameter (ifNotExists) expected string | integer | double | boolean | array | null, got object',
		'parameters' =>
		[
			'obj' => '{{"param"=>""}}'
		],
		'objectProperties' =>
		[
			'badNE1' => ['obj','param','{{}}']
		],
	],
	'badNE2' =>
	[
		//Invalid array element in ifNotExists
		//This isn't caught unless/until ifNotExists is evaluated.
		'error' => 'Error loading badNE2: dependency chain: badNE2: Expected array element to be one of: dependency id (string), array, null, or scalar constant, got object',
		'parameters' =>
		[
			'obj' => '{{"prop" => "asdf"}}'
		],
		'objectProperties' =>
		[
			'badNE2' => ['obj','propz',[1,2,'{{}}']]
		],
	],
	'ok1' =>
	[
		'output' => '"It works 1!"',
		'parameters' =>
		[
			'obj' => '{{"prop" => "It works 1!"}}'
		],
		'objectProperties' =>
		[
			'ok1' => ['obj','prop']
		],
	],
	'ok2' =>
	[
		//Default ifNotExists
		'output' => 'null',
		'parameters' =>
		[
			'obj' => '{{"prop" => "nope"}}'
		],
		'objectProperties' =>
		[
			'ok2' => ['obj','propz']
		],
	],
	'ok3' =>
	[
		//Specified ifNotExists
		'output' => '"It works 3!"',
		'parameters' =>
		[
			'obj' => '{{"prop" => "nope"}}',
			'default' => 'It works 3!',
		],
		'objectProperties' =>
		[
			'ok3' => ['obj','key','default']
		],
	],
	'ok4' =>
	[
		//Specified ifNotExists
		'output' => '{"c":1,"b":2,"z":{"0":1,"1":"It works 4!","z":3}}',
		'parameters' =>
		[
			'obj' => '{{"prop" => "nope"}}',
			'default' => 'It works 4!',
		],
		'objectProperties' =>
		[
			'ok4' => ['obj','propz',['c'=>1,'b'=>2,'z'=>[1,'default','z'=>3]]]
		],
	],

	'chain1' =>
	[
		//Chaining objectProperties
		'output' => '"Found it!"',
		'parameters' =>
		[
			'obj' => '{{"3rd" => "Found it!"}}',
			'lastResort' => 'Didn\'t find it',
		],
		'objectProperties' =>
		[
			'chain1' => ['obj','1st','link2'],
			'link2' => ['obj','2nd','link3'],
			'link3' => ['obj','3rd','lastResort'],
		],
	],
	'chain2' =>
	[
		//Chaining objectProperties falling back to ifNotExists
		'output' => '"Didn\'t find it"',
		'parameters' =>
		[
			'obj' => '{{"4th" => "Found it!"}}',
			'lastResort' => 'Didn\'t find it',
		],
		'objectProperties' =>
		[
			'chain2' => ['obj','1st','link2'],
			'link2' => ['obj','2nd','link3'],
			'link3' => ['obj','3rd','lastResort'],
		],
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
