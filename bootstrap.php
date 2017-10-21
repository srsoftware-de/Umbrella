<?php

include 'config.php';

define('INDEX_FETCH',PDO::FETCH_GROUP|PDO::FETCH_UNIQUE|PDO::FETCH_ASSOC);
const NO_CONVERSSION = 1;
const ARRAY_CONVERSION = 2;
const OBJECT_CONVERSION = 3;

function assert_failure($script, $line, $code, $message){
	error('Assertion failed in '.$script.', line '.$line.': '.$message);
	include 'common_templates/messages.php';
	include 'common_templates/closure.php';
	die();
}

function getUrl($service,$path=''){
	global $services;
	assert(isset($services[$service]['path']),'No '.$service.' service configured!');
	$url = $services[$service]['path'].$path;
	return $url;
}

function request($service = null,$path,$data = array(), $debug = false,$decode = ARRAY_CONVERSION){
	global $token;
	if ($service){
		$url = getUrl($service,$path);
	} else {
		$url = $path;
	}

	if ($data === null) $data = array();
	if (!isset($data['token'])) $data['token'] = $token;

	if ($debug) echo t('Sending post data to "?" :',$url).'<br/>';

	$ssl_options = array();
	$ssl_options['verify_peer'] = false; // TODO: this is rather bad. we need to sort this out!!!

	$options = [ 'ssl'=>$ssl_options];
	$post_data = http_build_query($data);
	if ($debug) echo t('Posting ?',print_r($data,true));
	$http_options = array();
	$http_options['method'] = 'POST';
	$http_options['header'] = 'Content-type: application/x-www-form-urlencoded';
	$http_options['content'] = $post_data;

	$options['http']=$http_options;

	$context = stream_context_create($options);
	$response = file_get_contents($url,false,$context);
	if ($debug) debug($response);
	switch ($decode){
		case ARRAY_CONVERSION:
			return json_decode($response,true);
			break;
		case OBJECT_CONVERSION:
			return json_decode($response);
			break;
	}
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
function error($message,$args = null){
	global $errors;
	$errors[] = t($message,$args);
}

function debug($object,$die = false){
	if ($object === null) {
		echo 'null';
	} else if ($object === false) {
		echo 'false';
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

function location(){
	$port = $_SERVER['SERVER_PORT'];
	return $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['SERVER_NAME'].($port == 80 || $port == 443?'':':'.$port).$_SERVER['REQUEST_URI'];
}

function getLocallyFromToken(){
	$db = get_or_create_db();
	$db->query('CREATE TABLE IF NOT EXISTS tokens (token VARCHAR(255) NOT NULL PRIMARY KEY, expiration INT NOT NULL, user_data TEXT NOT NULL);');

	$query = $db->prepare('SELECT * FROM tokens WHERE token = :token;');
	$params = array(':token' => $_SESSION['token']);
	assert($query->execute($params),'Was not able to request token table.');
	$rows = $query->fetchAll(PDO::FETCH_ASSOC);
	$time = time();
	$user = null;

	$query = $db->prepare('DELETE FROM tokens WHERE token = :token;');
	foreach ($rows as $index => $row){
		if ($row['expiration'] > $time){
			$user = json_decode($row['user_data']); // read user data
		} else 	$query->execute([':token'=>$row['token']]); // drop expired token
	}
	return $user;
}

/* uses the user service to validate the session token and get user data */
function validateToken(){
	global $user;
	$user = request('user', 'validateToken',['token'=>$_SESSION['token']],false,OBJECT_CONVERSION);
	if (is_object($user)){
		$token = $user->token;
		unset($user->token);
		$user_data = json_encode($user);
		$db = get_or_create_db();
		$params = [':token'=>$token->token,':user_data'=>$user_data,':exp'=>$token->expiration];
		$query = $db->prepare('INSERT OR IGNORE INTO tokens (token, user_data, expiration) VALUES (:token, :user_data, :exp);');
		assert($query->execute($params),'Was not able to store token in database.');
	} else $user = null;
}

/**
 * checks if a user is logged in and forces a login of not.
 */
function require_login(){
	global $services,$user;
	if ($_SESSION['token'] === null) redirect(getUrl('user','login?returnTo='.location()));
	$user = getLocallyFromToken();
	if ($user === null) validateToken();
	if ($user === null) redirect($services['user']['path'].'login?returnTo='.location());
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

function init(){
	global $user;
	$user = null;
	session_start();
	if (!isset($_SESSION['token'])){
		$_SESSION['token'] = param('token');
		if (isset($_GET['token'])) redirect('.'); // if token was appended to url: set cookie and reload
	}
}

function objectFrom($entity){
	if (is_array($entity)){
		$obj = new stdClass();
		foreach ($entity as $key => $val){
			$obj->$key = objectFrom($val);
		}
		return $obj;
	}
	return $entity;
}

assert_options(ASSERT_ACTIVE,   true);
assert_options(ASSERT_BAIL,     false);
assert_options(ASSERT_WARNING,  true);
assert_options(ASSERT_CALLBACK, 'assert_failure');

init();
