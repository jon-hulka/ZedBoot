<?php
//A couple of simple classes for demonstration
class Greeter
{
	public
		$greeting;
	public function __construct(string $greeting = 'Hello')
	{
		$this->greeting=$greeting;
	}
	public function greet(string $name)
	{
		return $this->greeting.', '.$name;
	}
	public function setGreeting(string $greeting)
	{
		$this->greeting=$greeting;
	}
}

class GreeterFactory
{
	public function getGreeter(string $greeting)
	{
		return new Greeter($greeting);
	}
}

$parameters =
[
	'name'=>'John',
	'englishGreeting'=>'Hello',
	'boolValue'=>false,
	'intValue'=>1,
	'greetingsArray'=>
	[
		'spanish'=>'Buenos días'
	],
	'greetingsObject'=> (object)['french'=>'Bonjour']
];

$arrayElements =
[
	//Dependency key
	'spanishGreeting'=>
	[
		//Dependency key of array
		'greetingsArray',
		//Array key
		'spanish'
	],
	//'other' will not be found in greetingsArray, so this will default to englishGreeting ('Hello')
	'otherGreeting'=>
	[
		'greetingsArray',
		'other',
		//dependency key or non-string scalar value to be loaded if the key is not found (optional, defaults to null)
		'englishGreeting'
	],
];
$objectProperties =
[
	//Dependency key
	'frenchGreeting'=>
	[
		//Dependency key of object
		'greetingsObject',
		//Property name
		'french',
		//dependency key or non-string scalar value to be loaded if the property is not found (optional, defaults to null)
		'englishGreeting'
	],
];
$services =
[
	'englishGreeter' =>
	[
		//Class name
		'Greeter',
		//Constructor arguments
		//Strings will be interpreted as dependency keys
		//Other scalar values can be used
		['englishGreeting'],
		//Singleton, default true - indicates that one instance will be reused,
		//rather than creating a new instance every time this dependency is required.
		true
	],
	//This greeter's greeting will be set up via $setterInjections
	'spanishGreeter' =>
	[
		'Greeter',
		[],
		true
	],
	//This greeter's greeting comes from an included config file (see $includes)
	'germanGreeter' =>
	[
		'Greeter',
		['germanGreeting'],
		true
	],
	'greeterFactory' =>
	[
		'GreeterFactory',
		[],
		true
	],
	'otherGreeter' =>
	[
		'Greeter',
		['otherGreeting'],
		true
	]
];

//Anything created by a getter can be set up in $factoryServices
$factoryServices =
[
	'frenchGreeter' =>
	[
		//Factory name
		'greeterFactory',
		//Getter function name
		'getGreeter',
		//Getter arguments
		//Strings will be interpreted as dependency keys
		//Other scalar values can be used
		['frenchGreeting'],
		//Singleton - same as in $services
		true
	],
	'greetInFrench' =>
	[
		'frenchGreeter',
		'greet',
		['name'],
		true
	],
];
//This is just a list of config files (absolute path) to be included
//All configuration settings will be included as part of this file's configuration
$includes =
[
	__DIR__.'/more_config.php'
];
$setterInjections =
[
	[
		//Dependency key
		'spanishGreeter',
		//Setter function name
		'setGreeting',
		//Setter arguments
		//Strings will be interpreted as dependency keys
		//Other scalar values can be used
		['spanishGreeting']
	]
];
