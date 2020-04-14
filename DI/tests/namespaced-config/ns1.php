<?php

$parameters =
[
	'arr1' => ['This','is','an','array'],
	'obj1' => (object) ['ib' => 'object']
];

$arrayElements =
[
	'ae1' => ['arr1',0],
	'ae2' => ['ns2:arr1',0],
	//The local namespace is explicit, it should not matter
	'ae3' => ['ns1:arr1',4,['ns2:arr1','ns2:ae1']],
	'ae4' => ['ns2:arr1',4],
	//Array included from another config script should have local namespace
	'ae5' => ['incarr1',0]
];

$objectProperties =
[
	'op1' => ['obj1','ib'],
	'op2' => ['ns2:obj1','ib'],
	'op3' => ['obj1','ir',['obj1','ns2:obj1']],
	'op4' => ['obj1','ir'],
];

$services =
[
];

$factoryServices =
[
];

$setterInjections =
[
];

$includes =
[
	__DIR__.'/include.php'
];
