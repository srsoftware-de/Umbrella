<?php

include 'bootstrap.php';


if (isset($_GET['username']) && isset($_GET['password'])){
	$token = request('user','login?username='.$_GET['username'].'&password='.$_GET['password']);
}

if ($token == null){
	include('user/form/login.php');
	die();
}

function getMenuEntries($service){
	globel $token;
	$response = request($service,'menu?token='.$token);
}
$menu_entries = array();
foreach ($services as $service => $path){
	$menu_entries[$service] = getMenuEntries($service);
}
die(print_r($menu_entries));
