<?php

include 'config.php';

define('INDEX_FETCH',PDO::FETCH_GROUP|PDO::FETCH_UNIQUE|PDO::FETCH_ASSOC);

function assert_failure($script, $line, $message){
	error('Assertion failed in '.$script.', line '.$line.': '.$message);
	include 'common_templates/messages.php';
	include 'common_templates/closure.php';
        die();
}

function getUrl($service,$path='',$add_token = false){
	global $services,$token;
	$url = $services[$service]['path'].$path;
	if (!$add_token) return $url; 
	if (strpos($url, '?')) return $url.'&token='.$token;
	return $url.'?token='.$token;
}

function request($service,$path,$debug = false,$decode = true){
	$url = getUrl($service,$path,true);
		
	if ($debug) echo $url.'<br/>';
	$context = stream_context_create(array('ssl'=>array('verify_peer'=>false)));
	$response = file_get_contents($url,false,$context);
	if ($debug) debug($response);
	if ($decode) return json_decode($response,true);
	return $response;
}

function post($name,$default = null){
	if (isset($_POST[$name])){
		$result = $_POST[$name];
		if (is_array($result) || is_object($result)) return $result;
		return trim($result);
	}
	return $default;
}

function param($name,$default = null){
	if (isset($_GET[$name])) {
		$result = $_GET[$name];
		if (is_array($result) || is_object($result)) return $result;
		return trim($result);
	}
	return post($name,$default);
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
	if ($object === null) {
		echo 'null';
	} else echo '<pre>'.print_r($object,true).'</pre>';
	if ($die){
		include 'common_templates/closure.php';
		die();
	}
}

function redirect($url){
	header('Location: '.$url);
	die();
}

function replace_text($text,$replacements = null){
	if ($replacements !== null){
		if (!is_array($replacements)) $replacements = array($replacements);
		while ($rep = array_shift($replacements)){
			$text = preg_replace('/\?/', $rep, $text,1);
		}
	}	
	return $text;
}

/**
 * contacts the user service, sends the current token and recieves the user data.
 * if no token is given, redirects to the login page
 */
function current_user(){
	global $token,$services;
	assert(isset($services['user']['path']),'No user service configured!');
	if ($token === null){
		redirect($services['user']['path'].'login');
	}
	$url = $services['user']['path'].'validateToken';

	$post_data = http_build_query(array('token'=>$token));
	$options = array(
		'http'=>array('method'=>'POST','header'=>'Content-type: application/x-www-form-urlencoded','content'=>$post_data),
		'ssl'=>array('verify_peer'=>false)); // TODO: this is rather bad. we need to sort this out!!!
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

function dialog($question,$options = array('YES'=>'?confirm=yes','NO'=>'index')){
	$result = '<fieldset class="dialog">'.$question.'</br>';
	foreach ($options as $text => $link){
		$result .= '<a href="'.$link.'">'.$text.'</a>&nbsp';
	}
	return $result.'</fieldset>';
}

function t($text,$replacements=null){
	global $lang;
	$lang_file ='lang.'.$lang.'.php';
	if (file_exists($lang_file)){
		include $lang_file;
		if (isset($translations[$text])) $text=$translations[$text];
	}
	return replace_text($text,$replacements);
}

assert_options(ASSERT_ACTIVE,   true);
assert_options(ASSERT_BAIL,     false);
assert_options(ASSERT_WARNING,  true);
assert_options(ASSERT_CALLBACK, 'assert_failure');

$errors = array();
$infos = array();
$user = null;

$token = param('token');
if (isset($_COOKIE['UmbrellaToken'])) $token = $_COOKIE['UmbrellaToken'];
	
