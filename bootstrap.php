<?php

include 'config.php';

function assert_failure($script, $line, $message){
	error('Assertion failed in '.$script.', line '.$line.': '.$message);
	include 'common_templates/messages.php';
	include 'common_templates/closure.php';
        die();
}

function getUrl($service,$path){
	global $services,$token;
	return $services[$service].$path.'?token='.$token;
}

function request($service,$path,$show_request = false){
	$url = getUrl($service,$path);
	if ($show_request) echo $url.'<br/>';
	$response = file_get_contents($url);
	return json_decode($response,true);
}

function post($name){
	if (isset($_POST[$name])) return $_POST[$name];
	return null;
}

function info($message){
	global $infos;
	$infos[] = $message;
}
function error($message){
	global $errors;
	$errors[] = $message;
}

function debug($object,$die = false){
	echo '<pre>'.print_r($object,true).'</pre>';
	if ($die){
		include 'common_templates/closure.php';
		die();
	}
}

/**
 * contacts the user service, sends the current token and recieves the user data.
 * if no token is given, redirects to the login page
 */
function current_user(){
	global $token,$services;
	assert(isset($services['user']['path']),'No user service configured!');
	if ($token === null){
		header('Location: '.$services['user']['path'].'login');
		die();
	}
	$url = $services['user']['path'].'validateToken';

	$post_data = http_build_query(array('token'=>$token));
	$options = array('http'=>array('method'=>'POST','header'=>'Content-type: application/x-www-form-urlencoded','content'=>$post_data));
	$context = stream_context_create($options);

	$json = file_get_contents($url,false,$context);
	$user = json_decode($json);
	return $user;
}

/**
 * checks if a user is logged in and forces a login of not. 
 */
function require_login(){
	global $user;
	$user = current_user();	
}

function postLink($url,$caption,$data = array(),$title = null){
	global $token;
	if ($token !== null && !isset($data['token'])) $data['token'] = $token;

	echo '<form method="POST" action="'.$url.'">';
	foreach ($data as $name => $value) echo '<input type="hidden" name="'.$name.'" value="'.$value.'" />';
	echo '<button type="submit">'.$caption.'</button>';
	echo '</form>';
	
}


assert_options(ASSERT_ACTIVE,   true);
assert_options(ASSERT_BAIL,     false);
assert_options(ASSERT_WARNING,  true);
assert_options(ASSERT_CALLBACK, 'assert_failure');

$errors = array();
$infos = array();
$token = null;
$user = null;

if (isset($_COOKIE['UmbrellaToken'])) $token = $_COOKIE['UmbrellaToken'];
	
