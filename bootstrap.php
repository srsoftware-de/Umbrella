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

function getUrl($service_name,$path=''){
	global $services;
	assert($service_name !== null,'No service handed to getUrl!');
	assert(isset($services[$service_name]['path']),'No '.$service_name.' service configured!');
	$url = $services[$service_name]['path'].$path;
	return $url;
}

function request($service = null,$path,$data = array(), $debug = false,$decode = ARRAY_CONVERSION){
	if ($service){
		$url = getUrl($service,$path);
	} else {
		$url = $path;
	}

	if ($data === null) $data = array();
	if (!isset($data['token'])) $data['token'] = $_SESSION['token'];

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

function save_file($filename,$file_contents,$mime){
	$url = getUrl('files','add');
	
	$boundary = '--------------------------'.microtime(true);
	$br = "\r\n";
	
	
	$content =  '--'.$boundary.$br.
				'Content-Disposition: form-data; name="file"; filename="'.basename($filename).'"'.$br.
				'Content-Type: '.$mime.$br.$br.$file_contents.$br;
	
	$content .=	'--'.$boundary.$br.
				'Content-Disposition: form-data; name="token"'.$br.$br.$_SESSION['token'].$br;

	$content .=	'--'.$boundary.$br.
				'Content-Disposition: form-data; name="dir"'.$br.$br.dirname($filename).$br;
	
	
	$content .= "--".$boundary.'--'.$br;
	
	$ssl_options = ['verify_peer' => false]; // TODO: this is rather bad. we need to sort this out!!!
	
	$http_options = array();
	$http_options['method'] = 'POST';
	$http_options['header'] = 'Content-Type: multipart/form-data; boundary='.$boundary;	
	$http_options['content'] = $content;

	$options = [
			'ssl' => $ssl_options,
			'http'=> $http_options,
	];
	
	$context = stream_context_create($options);	
	
	$response = file_get_contents($url,false,$context);
}

function send_mail($sender, $reciever, $subject = '', $text = '', $attachment = null){
	//debug(['from'=>$sender, 'to'=>$reciever, 'subject'=>$subject, 'text'=>$text],1);
	
	if ($attachment){
		$filename = $attachment['name'];
		
		$content = $attachment['content'];
		$content = chunk_split(base64_encode($content));
		$uid = md5(uniqid(time()));
		
		// header
		$header = "From: ".$sender." <".$sender.">\r\n";
		$header .= "Reply-To: ".$sender."\r\n";
		$header .= "MIME-Version: 1.0\r\n";
		$header .= "Content-Type: multipart/mixed; boundary=\"".$uid."\"\r\n\r\n";
		
		// message & attachment
		$nmessage = "--".$uid."\r\n";
		$nmessage .= "Content-type:text/plain; charset=utf-8\r\n";
		$nmessage .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
		$nmessage .= $text."\r\n\r\n";
		$nmessage .= "--".$uid."\r\n";
		$nmessage .= "Content-Type: application/octet-stream; name=\"".$filename."\"\r\n";
		$nmessage .= "Content-Transfer-Encoding: base64\r\n";
		$nmessage .= "Content-Disposition: attachment; filename=\"".$filename."\"\r\n\r\n";
		$nmessage .= $content."\r\n\r\n";
		$nmessage .= "--".$uid."--";
	} else {
		$header = "From: ".$sender;
		$nmessage = $text;
	}
	
	return mail($reciever, $subject, $nmessage, $header);
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

function info($message,$args = null){
	if ($message === null) return;
	$_SESSION['infos'][] = t($message,$args);
}

function warn($message,$args = null){
	if ($message === null) return;
	$_SESSION['warnings'][] = t($message,$args);
}


function error($message,$args = null){
	if ($message === null) return;
	$_SESSION['errors'][] = t($message,$args);
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

function query_insert($sql,$args){
	foreach ($args as $k => $v) $sql = str_replace($k,'"'.$v.'"',$sql);
	return $sql;
}

function redirect($url){
	header('Location: '.$url);
	die();
}

function replace_text($text,$replacements = null){
	if ($replacements !== null){
		if (!is_array($replacements)) $replacements = array($replacements);
		while ($rep = array_shift($replacements)) $text = preg_replace('/\?/', $rep, $text,1);
	}
	return $text;
}

function location($drop = []){
	if ($drop == '*'){
		$args = [];
	} else {
		if (!is_array($drop)) $drop = [$drop];
		$args = $_GET;
		foreach ($drop as $key) unset($args[$key]);
	}
	$port = $_SERVER['SERVER_PORT'];
	$get_string = empty($args)?'':'?'.http_build_query($args);
	return $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['SERVER_NAME'].($port == 80 || $port == 443?'':':'.$port).$_SERVER['REDIRECT_URL'].$get_string;
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
function validateToken($service_name = null){
	global $user;
	$user = request('user', 'validateToken',['token'=>$_SESSION['token'],'domain'=>getUrl($service_name)],false,OBJECT_CONVERSION);
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

function revoke_token($token){
	$db = get_or_create_db();
	$query = $db->prepare('DELETE FROM tokens WHERE token = :token');
	assert($query->execute(array(':token'=>$token)),'Was not able to execute DELETE statement.');
	unset($_SESSION['token']);	
}

/**
 * checks if a user is logged in and forces a login of not.
 */
function require_login($service_name = null){
	global $services,$user,$theme;
	
	$revoke = param('revoke');
	if ($revoke) die(revoke_token($revoke));
	
	assert($service_name !== null,'require_login called without a service name!');
	if ($_SESSION['token'] === null) redirect(getUrl('user','login?returnTo='.location()));
	$user = getLocallyFromToken();
	if ($user === null) validateToken($service_name);
	if ($user === null) redirect(getUrl('user','login?returnTo='.location()));
	session_write_close();
	if (isset($user->theme)) $theme = $user->theme;
}

function dialog($question,$options = array('YES'=>'?confirm=yes','NO'=>'index')){
	$result = '<fieldset class="dialog">'.$question.'</br>';
	foreach ($options as $text => $link){
		$result .= '<a class="button" href="'.$link.'">'.$text.'</a>&nbsp';
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
	if ($token_param = param('token')) $_SESSION['token'] = $token_param;
	if (isset($_GET['token'])) redirect(location(true)); // if token was appended to url: set cookie and reload
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

function conclude_vcard($vcard){
	$short = '';
	if (isset($vcard['FN'])) return $vcard['FN'];
	if (isset($vcard['N'])){
		$names = explode(';',$vcard['N']);
		return $names[1].' '.$names[0];
	}
	debug('error in conclude_vcard: no name set',true);
}

function address_from_vcard($vcard){
	$result = conclude_vcard($vcard)."\n";
	$address = $vcard['ADR'];
	if (is_array($address)){
		$address = reset($address);
	}
	$address = split(';', $address);
	if (!empty($address[0])) $result .= t('Postbox: ',$address[0])."\n"; // Postfach
	if (!empty($address[1])) $result .= $address[1]."\n";				// Adresszusatz
	if (!empty($address[2])) $result .=	$address[2]."\n";				// Straße
	if (!empty($address[5])) $result .=	$address[5]." ";				// Postleitzahl
	if (!empty($address[3])) $result .=	$address[3]."\n";				// Ort
	if (!empty($address[4])) $result .=	$address[4]." / ";				// Region
	if (!empty($address[6])) $result .=	$address[6];				// Land
	
	return $result;
}

assert_options(ASSERT_ACTIVE,   true);
assert_options(ASSERT_BAIL,     false);
assert_options(ASSERT_WARNING,  true);
assert_options(ASSERT_CALLBACK, 'assert_failure');

init();
