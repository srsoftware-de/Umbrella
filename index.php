<?php

include 'config.php';

$services = array('user','permission');

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

if (isset($_GET['username']) && isset($_GET['password'])){
	$token = request('user','login?username='.$_GET['username'].'&password='.$_GET['password']);
}

if ($token == null){
	include('user/form/login.php');
	die();
}

function getMenuEntries($service){
	$response = request($service,'menu');
}

$menu_entries = array();
foreach ($services as $service){
	$menu_entries[$service] = getMenuEntries($service);
}
die(print_r($menu_entries);
