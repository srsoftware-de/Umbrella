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
	echo file_get_contents(getUrl($service, 'form/menu'));	
}
