<?php
//This converts strings '{{"prop" => "value"}}' to objects
$postProcess=function(string $contents)
{
	return str_replace('}}\'',']',str_replace('\'{{','(object)[',$contents));
};
$configs=
[
	'tooMany' =>
	[
		'error' => 'arrayElements: "tooMany": must have no more than 3 parameters ( arrayId, arrayKey, optional ifNotExists )',
		'arrayElements' =>
		[
			'tooMany' => [1,2,3,4]
		],
	],
	'tooFew' =>
	[
		'error' => 'arrayElements: "tooFew": must have at least 2 parameters ( arrayId, arrayKey, optional ifNotExists )',
		'arrayElements' =>
		[
			'tooFew' => [1]
		],
	],
	'badId' =>
	[
		'error' => 'arrayElements: "badId": 1st parameter (arrayId) expected string, got array',
		'arrayElements' =>
		[
			'badId' => [[],'key']
		],
	],
	'badKey' =>
	[
		'rrayElements: "badKey": 2nd parameter (arrayKey) expected string | integer, got NULL',
		'arrayElements' =>
		[
			'badKey' => ['id',null]
		],
	],
	'notArray' =>
	[
		'error' => 'Error loading notArray: dependency chain: notArray: notArray arrayId: Expected str to be array, got string',
		'parameters' =>
		[
			'str' => 'test'
		],
		'arrayElements' =>
		[
			'notArray' => ['str',1]
		],
	],
	'badNE1' =>
	[
		'error' => 'arrayElements: "badNE1": 3rd parameter (ifNotExists) expected string | integer | double | boolean | array | null, got object',
		'parameters' =>
		[
			'arr' => ['','It works!']
		],
		'arrayElements' =>
		[
			'badNE1' => ['arr',1,'{{}}']
		],
	],
	'badNE2' =>
	[
		//Invalid array element in ifNotExists
		//This isn't caught unless/until ifNotExists is evaluated.
		'error' => 'Error loading badNE2: dependency chain: badNE2: Expected array element to be one of: dependency id (string), array, null, or scalar constant, got object',
		'parameters' =>
		[
			'arr' => ['','It works!']
		],
		'arrayElements' =>
		[
			'badNE2' => ['arr',2,[1,2,'{{}}']]
		],
	],
	'ok1' =>
	[
		'output' => '"It works 1!"',
		'parameters' =>
		[
			'arr' => ['key' => 'It works 1!']
		],
		'arrayElements' =>
		[
			'ok1' => ['arr','key']
		],
	],
	'ok2' =>
	[
		'output' => '"It works 2!"',
		'parameters' =>
		[
			'arr' => ['','It works 2!']
		],
		'arrayElements' =>
		[
			'ok2' => ['arr',1]
		],
	],
	'ok3' =>
	[
		//Default ifNotExists
		'output' => 'null',
		'parameters' =>
		[
			'arr' => ['nope','nope']
		],
		'arrayElements' =>
		[
			'ok3' => ['arr',3]
		],
	],
	'ok4' =>
	[
		//Specified ifNotExists
		'output' => '"It works 4!"',
		'parameters' =>
		[
			'arr' => ['nope','nope'],
			'default' => 'It works 4!',
		],
		'arrayElements' =>
		[
			'ok4' => ['arr','key','default']
		],
	],
	'ok5' =>
	[
		//Specified ifNotExists
		'output' => '{"c":1,"b":2,"z":{"0":1,"1":"It works 5!","z":3}}',
		'parameters' =>
		[
			'arr' => ['nope','nope'],
			'default' => 'It works 5!',
		],
		'arrayElements' =>
		[
			'ok5' => ['arr','key',['c'=>1,'b'=>2,'z'=>[1,'default','z'=>3]]]
		],
	],
	'chain1' =>
	[
		//Chaining arrayElements
		'output' => '"Found it!"',
		'parameters' =>
		[
			'arr' => ['3rd'=>'Found it!'],
			'lastResort' => 'Didn\'t find it',
		],
		'arrayElements' =>
		[
			'chain1' => ['arr','1st','link2'],
			'link2' => ['arr','2nd','link3'],
			'link3' => ['arr','3rd','lastResort'],
		],
	],
	'chain2' =>
	[
		//Chaining arrayElements falling back to ifNotExists
		'output' => '"Didn\'t find it"',
		'parameters' =>
		[
			'arr' => ['4th'=>'Found it!'],
			'lastResort' => 'Didn\'t find it',
		],
		'arrayElements' =>
		[
			'chain2' => ['arr','1st','link2'],
			'link2' => ['arr','2nd','link3'],
			'link3' => ['arr','3rd','lastResort'],
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
