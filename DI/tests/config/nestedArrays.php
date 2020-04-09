<?php
global $dependency;
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
$configs =
[
	'avBadArray1' =>
	[
		//Object buried in the ifNotExists array should throw an exception
		//This will only happen if the ifNotExists option is evaluated
		'parameters' =>
		[
			'arr' => ['a','b']
		],
		'arrayValues' =>
		[
			//Force ifNotExists evaluation by asking for non-existent arr[2]
			'avBadArray1' => ['arr',2,[[[(object)[]]],[]]]
		],
	],
	'avBadArray2' =>
	[
		//undefined dependency "x" in ifNotExists array should throw an exception
		//This will only happen if the ifNotExists option is evaluated
		'parameters' =>
		[
			'arr' => ['a','b']
		],
		'arrayValues' =>
		[
			//Force ifNotExists evaluation by asking for non-existent arr[2]
			'avBadArray2' => ['arr',2,[[['x']],[]]]
		],
	],
	'avNested' =>
	[
		//Should output [["This","is","an","array"],["This is a string",[3.141592653,1,2,3]]]
		'parameters' =>
		[
			'x' => ['This','is','an','array'],
			'y' => 'This is a string',
			'z' => 3.141592653,
			'arr' => []
		],
		'arrayValues' =>
		[
			//Force ifNotExists value
			'avNested' => ['arr',1,['x',['y',['z',1,2,3]]]]
		]
	],
	'objBadArray' =>
	[
		//Object buried in the ifNotExists array should cause an exception
		//This will only happen if the ifNotExists option is evaluated
		'parameters' =>
		[
			'obj' => (object) ['prop' => 'test']
		],
		'objectProperties' =>
		[
			//Force ifNotExists evaluation by asking for non-existent obj->ne
			'objBadArray' => ['obj','ne',[[[(object)[]]],[]]]
		],
	],
	'objNested' =>
	[
		//Should output [["This","is","an","array"],["This is a string",[3.141592653,1,2,3]]]
		'parameters' =>
		[
			'x' => ['This','is','an','array'],
			'y' => 'This is a string',
			'z' => 3.141592653,
			'obj' => (object)[]
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
		'services' =>
		[
			'svcBadArray' => ['Test',[[[(object)[]]],[]]]
		],
	],
	'svcNested' =>
	[
		//Should output [["test",[1,2,3],[3.14]]]
		'parameters' =>
		[
			'a' => [1,2,3],
			'b' => 'test',
		],
		'services' =>
		[
			'svc' => ['Test',[['b','a',[3.14]]]]
		],
		'factoryServices' =>
		[
			'svcNested' => ['svc','get']
		]
	],
	'fsvcBadArray' =>
	[
		//Object buried in factory service function's array parameter should cause an exception
		'services' =>
		[
			'factory' => ['TestFactory']
		],
		'factoryServices' =>
		[
			'fsvcBadArray' => ['factory','getTest',[[[(object)[]]],[]]]
		],
	],
	'fsvcNested' =>
	[
		//Should output [[{"param":"value"},"asdf",1,2,3]]
		'parameters' =>
		[
			'p1' => (object)['param'=>'value'],
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
