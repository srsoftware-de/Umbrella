<?php

function getUrl($path){
	$proto = 'http';
	if ($_SERVER['HTTPS'] == 'on') $proto.='s';
	$proto.='://';

	$host = $_SERVER['HTTP_HOST'];
	$script = dirname($_SERVER['SCRIPT_NAME']);

	return $proto.$host.$script.'/'.$path;
}

function request($path){
	$url = getUrl($path);
	echo $url.'<br/>';
	return file_get_contents($url);
}

$token = null;
if (isset($_GET['token'])) $token = $_GET['token'];

if (isset($_GET['username']) && isset($_GET['password'])){
	$token = request('user/login?username='.$_GET['username'].'&password='.$_GET['password']);
}

if ($token == null){
	include('user/form/login.php');
	die();
}
die(request('permission/get?token='.$token));
