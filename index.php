<?php

include 'bootstrap.php';


if (isset($_GET['username']) && isset($_GET['password'])){
	$token = request('user','login?username='.$_GET['username'].'&password='.$_GET['password']);
}

if ($token == null){
	include('user/form/login.php');
	die();
}

$menu_entries = array();
foreach ($services as $service => $path){
	$menu_entries[$service] = request($service,'menu?token='.$token);
}
foreach ($menu_entries as $service => $menu){
	foreach ($menu as $action => $text){
		print '<a href="'.$service.'/form/'.$action.'?token='.$token.'">'.$text.'</a> ';
	}
}
