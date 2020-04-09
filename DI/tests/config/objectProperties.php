<?php
global $dependency;
$configs=
[
	'tooMany' =>
	[
		'objectProperties' =>
		[
			'tooMany' => [1,2,3,4]
		],
	],
	'tooFew' =>
	[
		'objectProperties' =>
		[
			'tooFew' => [1]
		],
	],
	'badId' =>
	[
		'objectProperties' =>
		[
			'badId' => [[],'key']
		],
	],
	'badProp1' =>
	[
		'objectProperties' =>
		[
			'badProp1' => ['id',null]
		],
	],
	'badProp2' =>
	[
		'objectProperties' =>
		[
			'badProp' => ['id',2]
		],
	],
	'notObj' =>
	[
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
		'parameters' =>
		[
			'obj' => (object) ['param'=>'']
		],
		'objectProperties' =>
		[
			'badNE1' => ['obj','param',((object)[])]
		],
	],
	'badNE2' =>
	[
		//Invalid array element in ifNotExists
		//This isn't caught unless/until ifNotExists is evaluated.
		'parameters' =>
		[
			'obj' => (object) ['prop' => 'asdf']
		],
		'objectProperties' =>
		[
			'badNE2' => ['obj','propz',[1,2,((object)[])]]
		],
	],
	'ok1' =>
	[
		'parameters' =>
		[
			'obj' => (object) ['prop' => 'It works 1!']
		],
		'objectProperties' =>
		[
			'ok1' => ['obj','prop']
		],
	],
	'ok2' =>
	[
		//Default ifNotExists - should output null
		'parameters' =>
		[
			'obj' => (object) ['prop' => 'nope' ]
		],
		'objectProperties' =>
		[
			'ok2' => ['obj','propz']
		],
	],
	'ok3' =>
	[
		//Specified ifNotExists - should output 'It works 4!'
		'parameters' =>
		[
			'obj' => (object) ['prop' => 'nope'],
			'default' => 'It works 3!',
		],
		'objectProperties' =>
		[
			'ok3' => ['obj','key','default']
		],
	],
	'ok4' =>
	[
		//Specified ifNotExists - should output {"c":1,"b":2,"z":{"0":1,"1":"It works 4!","z":3}}
		'parameters' =>
		[
			'obj' => (object) ['prop' => 'nope'],
			'default' => 'It works 4!',
		],
		'objectProperties' =>
		[
			'ok4' => ['obj','propz',['c'=>1,'b'=>2,'z'=>[1,'default','z'=>3]]]
		],
	],

	'chain1' =>
	[
		//Chaining objectProperties - the ifNotExists chain will lead to 'Found it!'
		'parameters' =>
		[
			'obj' => (object) ['3rd'=>'Found it!'],
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
		//Chaining objectProperties - the ifNotExists chain will lead to "Didn't find it"
		'parameters' =>
		[
			'obj' => (object) ['4th'=>'Found it!'],
			'lastResort' => 'Didn\'t find it',
		],
		'objectProperties' =>
		[
			'chain2' => ['obj','1st','link2'],
			'link2' => ['obj','2nd','link3'],
			'link3' => ['obj','3rd','lastResort'],
		],
	],	'' =>
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
