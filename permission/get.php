<?php

include 'config.php';

function getUrl($service,$path){
	global $services;
	return $services[$service].$path;  
}

function request($script,$path){
	$url = getUrl($script,$path);
	echo $url.'<br/>';
	return file_get_contents($url);
}

if (!isset($_GET['token'])) die(NULL);
$token = $_GET['token'];
if ($token == null) die(NULL);

$response = request('user','validate?token='.$token);
if ($response == null) die(NULL);

$response = json_decode($response,true);

if (!isset($response['validity'])) die(NULL);
if (!isset($response['uid'])) die(NULL);
if (date('U') > $response['validity']) die(NULL);

if ($response['uid'] == 1) die('user.add');
die(NULL);

?>
