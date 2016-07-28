<?php

function getUrl($path){
	$proto = 'http';
	if ($_SERVER['HTTPS'] == 'on') $proto.='s'
	$proto.='://';
	
	$host = $_SERVER['HTTP_HOST'];
	$script = dirname($_SERVER['SCRIPT_NAME']);
	
	return $proto.$host.$script.'/'.$path;  
}

if (!isset($_GET['token'])) die(NULL);
$token = $_GET['token'];
if ($token == null) die(NULL);

die(getURL('user/validate?token='.$token'));
die(NULL);

?>
