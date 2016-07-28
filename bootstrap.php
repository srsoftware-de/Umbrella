<?php

include 'config.php';

function getUrl($service,$path){
	global $services;
	return $services[$service].$path;
}

function request($service,$path){
	$url = getUrl($service,$path);
	echo $url.'<br/>';
	return file_get_contents($url);
}

$token = null;
if (isset($_GET['token'])) $token = $_GET['token'];

