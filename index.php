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

if (!isset($_SESSION['token'])){
	if (isset($_GET['username']) && isset($_GET['password'])){
		$url = getUrl('user/login?username='.$_GET['username'].'&password='.$_GET['password']);
		$token = file_get_contents($url);
		die($token);
	}
	include('user/form/login.php');
	die();
}
die();
