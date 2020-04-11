<?php
class Test
{
	protected $v;
	public function __construct($v){ $this->v=$v; }
	public function get(){ return $this->v; }
}
class TestFactory
{
	public function getTest($v){ return new Test($v); }
}
//Converts strings '{{"prop" => "value"}}' to objects
$postProcess=function(string $contents)
{
	return str_replace('}}\'',']',str_replace('\'{{','(object)[',$contents));
};
$configs =
[
	'aeBadArray1' =>
	[
		//This will only happen if the ifNotExists option is evaluated
		'error' => 'Error loading aeBadArray1: dependency chain: aeBadArray1: Expected array element to be one of: dependency id (string), array, null, or scalar constant, got object',
		'parameters' =>
		[
			'arr' => ['a','b']
		],
		'arrayElements' =>
		[
			//Force ifNotExists evaluation by asking for non-existent arr[2]
			//'{{}}' will be replaced by an empty object
			'aeBadArray1' => ['arr',2,[[['{{}}']],[]]]
		],
		'services' =>
		[
			'eo'=>['EmptyObject']
		]
	],
	'aeBadArray2' =>
	[
		//This will only happen if the ifNotExists option is evaluated
		'error' => 'Error loading x: dependency chain: aeBadArray2 > x: Attempt to get undefined dependency: "x"',
		'parameters' =>
		[
			'arr' => ['a','b']
		],
		'arrayElements' =>
		[
			//Force ifNotExists evaluation by asking for non-existent arr[2]
			'aeBadArray2' => ['arr',2,[[['x']],[]]]
		],
	],
	'avNested' =>
	[
		'output' => '[["This","is","an","array"],["This is a string",[3.141592653,1,2,3]]]',
		'parameters' =>
		[
			'x' => ['This','is','an','array'],
			'y' => 'This is a string',
			'z' => 3.141592653,
			'arr' => []
		],
		'arrayElements' =>
		[
			//Force ifNotExists value
			'avNested' => ['arr',1,['x',['y',['z',1,2,3]]]]
		]
	],
	'objBadArray' =>
	[
		//This will only happen if the ifNotExists option is evaluated
		'error' => 'Error loading objBadArray: dependency chain: objBadArray: Expected array element to be one of: dependency id (string), array, null, or scalar constant, got object',
		'parameters' =>
		[
			'obj' => '{{"prop" => "test"}}'
		],
		'objectProperties' =>
		[
			//Force ifNotExists evaluation by asking for non-existent obj->ne
			'objBadArray' => ['obj','ne',[[['{{}}']],[]]]
		],
	],
	'objNested' =>
	[
		'output' => '[["This","is","an","array"],["This is a string",[3.141592653,1,2,3]]]',
		'parameters' =>
		[
			'x' => ['This','is','an','array'],
			'y' => 'This is a string',
			'z' => 3.141592653,
			'obj' => '{{}}'
		],
		'objectProperties' =>
		[
			//Force ifNotExists value
			'objNested' => ['obj','prop',['x',['y',['z',1,2,3]]]]
		]
	],
	'svcBadArray' =>
	[
		//Object buried in service constructor's array parameter should cause an exception
		'error' => 'Error loading svcBadArray: dependency chain: svcBadArray: Expected array element to be one of: dependency id (string), array, null, or scalar constant, got object',
		'services' =>
		[
			'svcBadArray' => ['Test',[[['{{}}']],[]]]
		],
	],
	'svcNested' =>
	[
		'output' => '["test",[1,2,3],[3.14]]',
		'parameters' =>
		[
			'a' => [1,2,3],
			'b' => 'test',
		],
		'services' =>
		[
			'svc' =>
			[
				'Test',
				[
					['b','a',[3.14]]
				]
			]
		],
		'factoryServices' =>
		[
			'svcNested' => ['svc','get']
		]
	],
	'fsvcBadArray' =>
	[
		//Object buried in factory service function's array parameter should cause an exception
		'error' => 'Error loading fsvcBadArray: dependency chain: fsvcBadArray: Expected array element to be one of: dependency id (string), array, null, or scalar constant, got object',
		'services' =>
		[
			'factory' => ['TestFactory']
		],
		'factoryServices' =>
		[
			'fsvcBadArray' => ['factory','getTest',[[['{{}}']],[]]]
		],
	],
	'fsvcNested' =>
	[
		'output' => '[[{"prop":"value"},"asdf",1,2,3]]',
		'parameters' =>
		[
			'p1' => '{{"prop"=>"value"}}',
			'p2' => 'asdf'
		],
		'services' =>
		[
			'factory' => ['TestFactory']
		],
		'factoryServices' =>
		[
			'svc' =>
			[
				'factory',
				'getTest',
				[
					[['p1','p2',1,2,3]]
				]
			],
			'fsvcNested' => ['svc', 'get']
		]
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
