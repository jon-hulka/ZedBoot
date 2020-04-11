<?php
class Test
{
	protected $v;
	public function __construct($v){ $this->v=$v; }
	public function get(){ return $this->v; }
	public function set($v){ $this->v=$v; }
}
class TestFactory
{
	protected $i=0;
	public function getTest(){ return new Test($this->i++); }
}
class TestInc
{
	protected static $current=0;
	protected $v;
	public static function reset(){ static::$current=0; }
	public function __construct(){ $this->v=static::$current++; }
	public function get(){ return $this->v; }
}
class TestCat
{
	public function cat(...$tests)
	{
		$parts=[];
		foreach($tests as $test) $parts[]=$test->get();
		return implode(', ',$parts);
	}
}
$postProcess=function(string $content)
{
	//Reset counter for singleton tests
	TestInc::reset();
	return $content;
};

$configs=
[
	'tooMany' =>
	[
		'error' => 'services: "tooMany": must have no more than 3 parameters ( className, optional arguments, optional singleton )',
		'services' =>
		[
			'tooMany' => [1,2,3,4]
		],
	],
	'tooFew' =>
	[
		'error' => 'services: "tooFew": must have at least 1 parameter ( className, optional arguments, optional singleton )',
		'services' =>
		[
			'tooFew' => []
		],
	],
	'badName' =>
	[
		'error' => 'services: "badName": 1st parameter (className) expected string, got integer',
		'services' =>
		[
			'badName' => [1]
		],
	],
	'badArgs' =>
	[
		'error' => 'services: "badArgs": 2nd parameter (arguments) expected array, got string',
		'services' =>
		[
			'badArgs' => ['Test','test']
		],
	],
	'badSingleton' =>
	[
		'error' => 'services: "badSingleton": 3rd parameter (singleton, optional) expected boolean, got string',
		'services' =>
		[
			'badSingleton' => ['Test', ['test'], 'test']
		],
	],
	'ok1' =>
	[
		'output' => '"OK 1"',
		'parameters' =>
		[
			'v' => 'OK 1'
		],
		'services' =>
		[
			'svc' =>
			[
				'Test',
				[
					'v'
				],
				true
			]
		],
		'factoryServices' =>
		[
			'ok1' => ['svc','get']
		],
	],
	'singleton1' =>
	[
		//factoryService definition singleton by default
		//The sequence will not be incremented
		'output' => '"0, 0, 0"',
		'services' =>
		[
			'factory' => ['TestFactory'],
			'cat' => ['TestCat'],
		],
		'factoryServices' =>
		[
			'svc' => ['factory','getTest'],
			'singleton1' => ['cat','cat',['svc','svc','svc']]
		],
	],
	'singleton2' =>
	[
		//factoryService definition explicitly singleton
		//The sequence will not be incremented
		'output' => '"0, 0, 0"',
		'services' =>
		[
			'factory' => ['TestFactory'],
			'cat' => ['TestCat'],
		],
		'factoryServices' =>
		[
			'svc' => ['factory','getTest',[],true],
			'singleton2' => ['cat','cat',['svc','svc','svc']]
		],
	],
	'nonSingleton1' =>
	[
		//factoryService definition not a singleton
		//The sequence will be incremented
		'output' => '"0, 1, 2"',
		'services' =>
		[
			'factory' => ['TestFactory'],
			'cat' => ['TestCat'],
		],
		'factoryServices' =>
		[
			'svc' => ['factory','getTest',[],false],
			'nonSingleton1' => ['cat','cat',['svc','svc','svc']]
		],
	],
	'singleton3' =>
	[
		//service definition singleton by default
		//The sequence will not be incremented
		'output' => '"0, 0, 0"',
		'services' =>
		[
			'svc' => ['TestInc'],
			'cat' => ['TestCat']
		],
		'factoryServices' =>
		[
			'singleton3' => ['cat','cat',['svc','svc','svc']]
		],
	],
	'singleton4' =>
	[
		//service definition explicitly singleton
		//The sequence will not be incremented
		'output' => '"0, 0, 0"',
		'services' =>
		[
			'svc' => ['TestInc'],
			'cat' => ['TestCat']
		],
		'factoryServices' =>
		[
			'singleton4' => ['cat','cat',['svc','svc','svc'],true]
		],
	],
	'nonSingleton2' =>
	[
		//service definition not a singleton
		//The sequence will be incremented
		'output' => '"0, 1, 2"',
		'services' =>
		[
			'svc' => ['TestInc',[],false],
			'cat' => ['TestCat']
		],
		'factoryServices' =>
		[
			'nonSingleton2' => ['cat','cat',['svc','svc','svc']]
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
