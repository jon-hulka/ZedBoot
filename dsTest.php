<?php
include 'DataStore/DataStoreInterface.class.php';
include 'DataStore/FileDataStore.class.php';

$byMarker=[];
$byId=[];
$now=time();
for($i=0;$i<100000; $i++)
{
	$marker=substr(base64_encode(openssl_random_pseudo_bytes(8)),3,35);
	$dep=str_shuffle('foo/bar/baz/biz/booze/bippity/boppity/boop');
	do
	{
		$id=random_int(0,100000000);
	}
	while(array_key_exists($id,$byId));
	$byMarker[$marker]=
	[
		'dependency'=>$dep,
		'id'=>$id,
		'expiry'=>$now+random_int(-5,15),
	];
	if(!array_key_exists($dep,$byId)) $byId[$dep]=[];
	$byId[$dep][$id]=$marker;
}
$ds=new \ZedBoot\DataStore\FileDataStore('/dev/shm/test.json');
$ds->quickWrite(['by_marker'=>$byMarker,'by_id'=>$byId]);

echo '
file size: '.filesize('/dev/shm/test.json').'
';
$start=microtime(true);
$data=$ds->lockAndRead();
echo 'read time: '.(microtime(true)-$start).'
';
$byMarker=&$data['by_marker'];
$byId=&$data['by_id'];
$removed=0;
$toRemove=[];
foreach($byMarker as $k=>$params)
{
	if($params['expiry']<$start)
	{
		$toRemove[]=[$params['dependency'],$params['id']];
		$removed++;
	}
}
foreach($toRemove as $id)
{
	unset($byMarker[$byId[$id[0]][$id[1]]]);
	unset($byId[$id[0]][$id[1]]);
}
echo 'process time: '.(microtime(true)-$start).'
';
$ds->writeAndUnlock($data);
echo 'time: '.(microtime(true)-$start).'
file size: '.filesize('/dev/shm/test.json').'
removed: '.$removed;
