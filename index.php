<?php

function getUrl($path){
	$proto = 'http';
	if ($_SERVER['HTTPS'] == 'on') $proto.='s';
	$proto.='://';

	$host = $_SERVER['HTTP_HOST'];
	$script = dirname($_SERVER['SCRIPT_NAME']);

	return $proto.$host.$script.'/'.$path;
}

session_start();
	
if (isset($_GET['username']) && isset($_GET['password'])){
	$url = getUrl('user/login?username='.$_GET['username'].'&password='.$_GET['password']);
	$token = file_get_contents($url);
	if ($token == null) {
		unset($_SESSION['token']);
	} else {
		$_SESSION['token'] = $token;
	}
}

if (!isset($_SESSION['token'])){
	include('user/form/login.php');
	die();
}

$url = getUrl('permission/get?token='.$_SESSION['token']);
die(file_get_contents($url));
