<?php
global $dependency;
$configs=
[
	'tooMany' =>
	[
		'arrayValues' =>
		[
			'tooMany' => [1,2,3,4]
		],
	],
	'tooFew' =>
	[
		'arrayValues' =>
		[
			'tooFew' => [1]
		],
	],
	'badId' =>
	[
		'arrayValues' =>
		[
			'badId' => [[],'key']
		],
	],
	'badKey' =>
	[
		'arrayValues' =>
		[
			'badKey' => ['id',null]
		],
	],
	'notArray' =>
	[
		//Invalid ifNotExists type: object
		'parameters' =>
		[
			'str' => 'test'
		],
		'arrayValues' =>
		[
			'notArray' => ['str',1]
		],
	],
	'badNE1' =>
	[
		//Invalid ifNotExists type: object
		'parameters' =>
		[
			'arr' => ['','It works!']
		],
		'arrayValues' =>
		[
			'badNE1' => ['arr',1,((object)[])]
		],
	],
	'badNE2' =>
	[
		//Invalid array element in ifNotExists
		//This isn't caught unless/until ifNotExists is evaluated.
		'parameters' =>
		[
			'arr' => ['','It works!']
		],
		'arrayValues' =>
		[
			'badNE2' => ['arr',2,[1,2,((object)[])]]
		],
	],
	'ok1' =>
	[
		'parameters' =>
		[
			'arr' => ['key' => 'It works 1!']
		],
		'arrayValues' =>
		[
			'ok1' => ['arr','key']
		],
	],
	'ok2' =>
	[
		'parameters' =>
		[
			'arr' => ['','It works 2!']
		],
		'arrayValues' =>
		[
			'ok2' => ['arr',1]
		],
	],
	'ok3' =>
	[
		//Default ifNotExists - should output null
		'parameters' =>
		[
			'arr' => ['nope','nope']
		],
		'arrayValues' =>
		[
			'ok3' => ['arr',3]
		],
	],
	'ok4' =>
	[
		//Specified ifNotExists - should output 'It works 4!'
		'parameters' =>
		[
			'arr' => ['nope','nope'],
			'default' => 'It works 4!',
		],
		'arrayValues' =>
		[
			'ok4' => ['arr','key','default']
		],
	],
	'ok5' =>
	[
		//Specified ifNotExists - should output {"c":1,"b":2,"z":{"0":1,"1":"It works 5!","z":3}}
		'parameters' =>
		[
			'arr' => ['nope','nope'],
			'default' => 'It works 5!',
		],
		'arrayValues' =>
		[
			'ok5' => ['arr','key',['c'=>1,'b'=>2,'z'=>[1,'default','z'=>3]]]
		],
	],
	'chain1' =>
	[
		//Chaining arrayValues - the ifNotExists chain will lead to 'Found it!'
		'parameters' =>
		[
			'arr' => ['3rd'=>'Found it!'],
			'lastResort' => 'Didn\'t find it',
		],
		'arrayValues' =>
		[
			'chain1' => ['arr','1st','link2'],
			'link2' => ['arr','2nd','link3'],
			'link3' => ['arr','3rd','lastResort'],
		],
	],
	'chain2' =>
	[
		//Chaining arrayValues - the ifNotExists chain will lead to "Didn't find it"
		'parameters' =>
		[
			'arr' => ['4th'=>'Found it!'],
			'lastResort' => 'Didn\'t find it',
		],
		'arrayValues' =>
		[
			'chain2' => ['arr','1st','link2'],
			'link2' => ['arr','2nd','link3'],
			'link3' => ['arr','3rd','lastResort'],
		],
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
