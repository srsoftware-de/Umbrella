<?php

include 'config.php';

function getUrl($service,$path){
	global $services;
	return $services[$service].$path;
}

function request($service,$path,$show_request = false){
	$url = getUrl($service,$path);
	if ($show_request) echo $url.'<br/>';
	$response = file_get_contents($url);
	return json_decode($response,true);
}

$token = null;
if (isset($_GET['token'])) $token = $_GET['token'];