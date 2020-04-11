<?php
//global $dependency;

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
$configs =
[
	//Dependency cycle between arrayElements and objectProperties
	'aeObj' =>
	[
		'error' => 'Circular dependency: aeObj > obj > aeObj',
		'arrayElements' =>
		[
			'aeObj' =>
			[
				'obj',
				'key'
			],
		],
		'objectProperties' =>
		[
			'obj' =>
			[
				'aeObj',
				'prop'
			],
		],
	],
	'aeAe' =>
	[
		//Dependency cycle between arrayElements
		'error' => 'Circular dependency: aeAe > aeAe',
		'arrayElements' =>
		[
			'aeAe' =>
			[
				'aeAe',
				'key'
			]
		]
	],
	'objObj' =>
	[
		//Dependency cycle between objectProperties
		'error' => 'Circular dependency: objObj > objObj',
		'objectProperties' =>
		[
			'objObj' =>
			[
				'objObj',
				'prop'
			]
		]
	],
	'svcSvc' =>
	[
		//Dependency cycle between services
		'error' => 'Circular dependency: svcSvc > svcSvc',
		'services' =>
		[
			'svcSvc' =>
			[
				'Foo',
				['svcSvc'],
				true
			]
		]
	],
	'svcFac' =>
	[
		//Dependency cycle between service, factoryService and factory
		//In this case, the factory depends on the service it is building (constructor parameter)
		'error' => 'Circular dependency: svcFac > fs > factory > svcFac',
		'services' =>
		[
			'svcFac' =>
			[
				'Foo',
				['fs'],
				true
			],
			'factory' =>
			[
				'FooFactory',
				['svcFac'],
				true
			],
		],
		'factoryServices' =>
		[
			'fs' =>
			[
				'factory',
				'getFoo',
				[],
				true
			],
		]
	],
	'svcFs' =>
	[
		//Dependency cycle between service and factoryService
		//svcFs requires fs, getter for fs requires svcFs
		'error' => 'Circular dependency: svcFs > fs > svcFs',
		'services' =>
		[
			'svcFs' =>
			[
				'Foo',
				['fs']
			],
			'factory' =>
			[
				'FooFactory'
			]
		],
		'factoryServices' =>
		[
			'fs' =>
			[
				'factory',
				'getFoo',
				['svcFs']
			]
		]
	],
	'fsFs' =>
	[
		//Dependency cycle between factoryServices
		'error' => 'Circular dependency: fsFs > fsFs',
		'services' =>
		[
			'factory' =>
			[
				'FooFactory'
			]
		],
		'factoryServices' =>
		[
			'fsFs' =>
			[
				'factory',
				'getFoo',
				['fsFs']
			]
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
//if(array_key_exists($dependency,$configs)) extract($configs[$dependency]);
