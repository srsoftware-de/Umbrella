<?php

include 'bootstrap.php';

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
foreach ($services as $service => $path){
	$menu_entries[$service] = getMenuEntries($service);
}
die(print_r($menu_entries));