<?php

$scripts[]=array();
$scripts['user']='https://eldorado.keawe.de:816/microservices/user/';

function getUrl($script,$path){
	global $scripts;
		
	return $scripts[$script].$path;  
}

function request($script,$path){
	$url = getUrl($script,$path);
	echo $url.'<br/>';
	return file_get_contents($url);
}

if (!isset($_GET['token'])) die(NULL);
$token = $_GET['token'];
if ($token == null) die(NULL);

die(request('user','validate?token='.$token));

?>
