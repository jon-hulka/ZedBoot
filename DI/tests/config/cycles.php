<?php
class FooFactory
{
	protected static $id=0;
	public function getFoo()
	{
		return new Foo($this->id++);
	}
}
class Foo
{
	protected $id;
	public function __construct($id)
	{
		$this->id=$id;
	}
	public function getId(){ return $this->id; }
}
$parameters =
[
];
$arrayValues =
[
	//arrayValue > objectProperty cycle
	'av.obj.cycle' =>
	[
		'obj.av.cycle',
		'key'
	],
	//arrayValue > arrayValue cycle
	'av.av.cycle' =>
	[
		'av.av.cycle',
		'key'
	]
];
$objectProperties =
[
	//objectProperty > objectProperty cycle
	'obj.obj.cycle' =>
	[
		'obj.obj.cycle',
		'prop'
	],

	//objectProperty > arrayValue cycle
	'obj.av.cycle' =>
	[
		'av.obj.cycle',
		'prop'
	],
];
$services =
[
	//service > service cycle
	'svc.cycle' =>
	[
		'Foo',
		['svc.cycle'],
		true
	],
	//service > factory > service cycle
	//In this case, the factory depends on the service it is building
	'svc.fs.cycle1' =>
	[
		'Foo',
		['fs.svc.cycle1'],
		true
	],
	'svc.fs.cycleFactory1' =>
	[
		'FooFactory',
		['svc.fs.cycle1'],
		true
	],
	
	//service > factory > service cycle
	//In this case, the factory is called with the service it is building as a parameter
	'svc.fs.cycle2' =>
	[
		'Foo',
		['fs.svc.cycle2'],
		true
	],
	'svc.factory' =>
	[
		'FooFactory',
		[],
		true
	],
];
$factoryServices =
[
	//factory > factory cycle
	'fs.cycle' =>
	[
		'svc.factory',
		'getFoo',
		['fs.cycle'],
		true
	],
	'fs.svc.cycle1' =>
	[
		'svc.fs.cycleFactory1',
		'getFoo',
		[],
		true
	],
	'fs.svc.cycle2' =>
	[
		'svc.fs.factory',
		'getFoo',
		['svc.fs.cycle2'],
		true
	],
];
$includes =
[
	
];
