<?php

include 'config.php';

define('INDEX_FETCH',PDO::FETCH_GROUP|PDO::FETCH_UNIQUE|PDO::FETCH_ASSOC);
const DS = '/';
const NO_CONVERSION = 1;
const ARRAY_CONVERSION = 2;
const OBJECT_CONVERSION = 3;
const DOM_CONVERSION = 4;

const PROJECT_STATUS_OPEN = 10;
const PROJECT_STATUS_STARTED = 20;
const PROJECT_STATUS_PENDING = 40;
const PROJECT_STATUS_COMPLETE = 60;
const PROJECT_STATUS_CANCELED = 100;

const TASK_STATUS_OPEN = 10;
const TASK_STATUS_STARTED = 20;
const TASK_STATUS_PENDING = 40;
const TASK_STATUS_COMPLETE = 60;
const TASK_STATUS_CANCELED = 100;

/** @var mixed $user created by static method, but used globally */
/** @var mixed $theme created by static method, but used globally */
/** @var mixed $translations created by static method, but used globally */

function address_from_vcard($vcard){
	$result = '';
	if (!empty($vcard->ORG)) {
		$result .= $vcard->ORG."\n";
	} elseif (!empty($vcard->FN)) {
		$result .= $vcard->FN."\n";
	}
	if (!empty($vcard->N)) {
		$name = trim($vcard->N->given.' '.$vcard->N->family);
		if (!empty($name)) $result .= $name."\n";
	}
	$address = $vcard->ADR;
	if (is_array($address)) $address = reset($address);
	if (!empty($address->post_box))  $result .= t('Postbox: ◊',$address->post_box)."\n"; // Postfach
	if (!empty($address->ext_addr))  $result .= $address->ext_addr."\n";				// Adresszusatz
	if (!empty($address->street))    $result .=	$address->street."\n";				// Straße
	if (!empty($address->post_code)) $result .=	$address->post_code." ";				// Postleitzahl
	if (!empty($address->locality))  $result .=	$address->locality."\n";				// Ort
	if (!empty($address->region))    $result .=	$address->region." / ";				// Region
	if (!empty($address->country))   $result .=	$address->country;				// Land

	return $result;
}

function assert_failure($script, $line, $code, $message){
	error_log('Assertion failed in '.$script.', line '.$line.': '.$message);
	error('Assertion failed in '.$script.', line '.$line.': '.$message);
	include 'common_templates/messages.php';
	include 'common_templates/closure.php';
	die();
}

function conclude_vcard($vcard){
	$result = '';
	if (isset($vcard->FN)) $result = trim($vcard->FN);
	if ($result != '') return $result;
	if (isset($vcard->N)) $result = trim($vcard->N->given.' '.$vcard->N->family);
	if ($result != '') return $result;
	debug('error in conclude_vcard: no name set',true);
}

function debug($object,$die = false,$exclude = false){
	if ($object === null) {
		echo 'null';
	} else if ($object === false) {
		echo 'false';
	} else {
		if ($exclude){
			if (!is_array($exclude)) $exclude = [$exclude];
			if (is_array($object)){
				foreach ($exclude as $key) unset($object[$key]);
			} else foreach ($exclude as $key) unset($object->{$key});
		}
		echo '<pre>'.print_r($object,true).'</pre>';
	}
	if ($die){
		include 'common_templates/closure.php';
		die();
	}
}

function dialog($question,$options = array('YES'=>'?confirm=yes','NO'=>'index')){
	$result = '<fieldset class="dialog">'.$question.'</br>';
	foreach ($options as $text => $link){
		$result .= '<a class="button" href="'.$link.'">'.$text.'</a>&nbsp';
	}
	return $result.'</fieldset>';
}

function emphasize($text,$key){
	$prefix = '<span class="hit">';
	$postfix = '</span>';

	$offset = strlen($prefix)+strlen($postfix);
	$len = strlen($key);
	$pos = stripos($text, $key);
	while ($pos !== false){
		$text = substr($text, 0,$pos).$prefix.substr($text,$pos,$len).$postfix.substr($text, $pos+$len);
		$pos = stripos($text, $key, $pos+$len+$offset);
	}
	return $text;
}

function error($message,$args = null){
	if ($message instanceof Exception) $message = $message->getMessage();
	if ($message === null) return;
	$message = t($message,$args);
	$_SESSION['errors'][crc32($message)] = $message;
	return false;
}

function field_description($field,$props){
	switch ($field){
		case 'UNIQUE':
		case 'PRIMARY KEY':
			return $field .' ('.implode(', ',$props).')';

		default:
			$sql = $field . ' ';
			if (is_array($props)){
				foreach ($props as $prop_k => $prop_v){
					switch (true){
						case $prop_k==='VARCHAR':
							$sql.= 'VARCHAR('.$prop_v.') '; break;
						case $prop_k==='DEFAULT':
							$sql.= 'DEFAULT '.($prop_v === null?'NULL ':'"'.$prop_v.'" '); break;
						case $prop_k==='KEY':
							if ($prop_v != 'PRIMARY') throw new Exception('Non-primary keys not implemented in document/controller.php!');
							$sql.= 'PRIMARY KEY '; break;
						default:
							$sql .= $prop_v.' ';
					}
				}
				$sql .= ", ";
			} else $sql .= $props.", ";
			return $sql;
	}
}

function generateRandomString(){
	return bin2hex(openssl_random_pseudo_bytes(40));
}

function getLocallyFromToken(){
	global $theme;
	$db = get_or_create_db();
	$db->query('CREATE TABLE IF NOT EXISTS tokens (token VARCHAR(255) NOT NULL PRIMARY KEY, expiration INT NOT NULL, user_data TEXT NOT NULL);');

	$query = $db->prepare('SELECT * FROM tokens WHERE token = :token;');
	$params = array(':token' => $_SESSION['token']);
	if (!$query->execute($params)) throw new Exception('Was not able to request token table.');
	$rows = $query->fetchAll(PDO::FETCH_ASSOC);
	$time = time();
	$user = null;

	$query = $db->prepare('DELETE FROM tokens WHERE token = :token;');
	foreach ($rows as $row){
		if ($row['expiration'] > $time){
			$user = json_decode($row['user_data']); // read user data
		} else $query->execute([':token'=>$row['token']]); // drop expired token
	}
	if ($user != null && !empty($user->theme)) $theme = $user->theme;
	return $user;
}

function getUrl($service_name,$path=''){
	global $services;
	if ($service_name == null) throw new Exception('No service handed to getUrl!');
	if (!isset($services[$service_name]['path'])) throw new Exception('No '.$service_name.' service configured!');
	return $services[$service_name]['path'].str_replace(" ", "%20", $path);
}

function html2plain($text){
	$text = str_replace(['<br/>','<br />','<br>'],"\n",$text);
	$text = str_replace(['<li>'],"- ",$text);
	$text = str_replace(['</li>','<ul>','</ul>'],'',$text);
	$text = str_replace('"','""',$text);
	return $text;
}

function info($message,$args = null){
	if ($message === null) return;
	$message = t($message,$args);
	$_SESSION['infos'][crc32($message)] = $message;
}

function init(){
	global $user;
	$user = null;
	session_start();
	if ($token_param = param('token')) $_SESSION['token'] = $token_param;
	if (isset($_GET['token'])) redirect(location('token')); // if token was appended to url: set cookie and reload
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
	$uri = strtok($_SERVER['REQUEST_URI'],'?');
	$location = (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) ? $_SERVER['HTTP_X_FORWARDED_PROTO'] : $_SERVER['REQUEST_SCHEME']).'://'.$_SERVER['SERVER_NAME'].($port == 80 || $port == 443?'':':'.$port).$uri.$get_string;
	return str_replace(" ", "%20", $location);
}

function markdown($text){
    
    if (file_exists('/opt/plantuml.jar')) $text = transformUml($text);
    
	if (file_exists('lib/parsedown')){
		include_once 'lib/parsedown/Parsedown.php';
		if (file_exists('lib/parsedown-extra')){
			include_once 'lib/parsedown-extra/ParsedownExtra.php';
			return ParsedownExtra::instance()->parse($text);
		}
		return Parsedown::instance()->parse($text);
	}

	if (file_exists('../lib/parsedown')){
		include_once '../lib/parsedown/Parsedown.php';
		if (file_exists('../lib/parsedown-extra')){
			include_once '../lib/parsedown-extra/ParsedownExtra.php';
			return ParsedownExtra::instance()->parse($text);
		}
		return Parsedown::instance()->parse($text);
	}	

	return str_replace("\n", "<br/>", htmlentities($text));
}


function module_version(){
	if (!defined('MODULE')) return '';
	if (empty($_SESSION['modules'])) $_SESSION['modules'] = [];
	if (!isset($_SESSION['modules'][MODULE])) $_SESSION['modules'][MODULE] = exec('git show -s --format=%ci HEAD');
	return '"'.MODULE.'" module ('.$_SESSION['modules'][MODULE].') - ';
}

function no_error(){
	return empty($_SESSION['errors']);
}

function objectFrom($entity){
	if (is_array($entity)){
		$obj = new stdClass();
		foreach ($entity as $key => $val) $obj->$key = objectFrom($val);
		return $obj;
	}
	return $entity;
}

function param($name,$default = null){
	if (isset($_GET[$name])) {
		$result = $_GET[$name];
		if (is_array($result) || is_object($result)) return $result;
		return trim($result);
	}
	return post($name,$default);
}

function post($name,$default = null){
	if (isset($_POST[$name])){
		$result = $_POST[$name];
		if (is_array($result) || is_object($result)) return $result;
		return trim($result);
	}
	return $default;
}

function project_state($state){
	switch ($state){
		case PROJECT_STATUS_CANCELED: return 'canceled';
		case PROJECT_STATUS_PENDING: return 'pending';
		case PROJECT_STATUS_OPEN: return 'open';
		case PROJECT_STATUS_COMPLETE: return 'completed';
		case PROJECT_STATUS_STARTED: return 'started';
	}
	return 'unknown';
}

function query_insert($query,$args){
	$sql = ($query instanceof PDOStatement ? $query->queryString : $query) . ' ';
	$pos = strpos($sql,'?');
	if ($pos > 0){
		while ($pos > 0){
			$v = array_shift($args);
			$t = $v === null ? 'NULL':'"'.$v.'"';
			$sql = substr_replace($sql,$t,$pos,1);
			$pos = strpos($sql,'?');
		}
	} else {
		foreach ($args as $k => $v){
			$t = $v === null ? 'NULL':'"'.$v.'"';
			$sql = str_replace($k.',',$t.',',$sql);
			$sql = str_replace($k.' ',$t.' ',$sql);
		}
	}
	return $sql;
}

function redirect($url){
	header('Location: '.$url);
	die();
}

function replace_text($text,$replacements = null){
	if ($replacements !== null){
		if (!is_array($replacements)) $replacements = [$replacements];
		foreach ($replacements as $rep) $text = preg_replace('/◊/', $rep, $text,1);
	}
	return $text;
}

function request($service = null,$path,$data = [], $debug = false,$decode = ARRAY_CONVERSION){
	if ($service){
		$url = getUrl($service,$path);
	} else {
		$url = $path;
	}

	if ($data === null) $data = array();
	if (!isset($data['token'])) $data['token'] = $_SESSION['token'];

	if ($debug) echo t('Sending post data to "◊" :',$url).'<br/>';

	$ssl_options = array();
	$ssl_options['verify_peer'] = false; // TODO: this is rather bad. we need to sort this out!!!

	$options = [ 'ssl'=>$ssl_options];
	$post_data = http_build_query($data);
	if ($debug) echo t('Posting ◊',print_r($data,true));
	$http_options = array();
	$http_options['method'] = 'POST';
	$http_options['header'] = 'Content-type: application/x-www-form-urlencoded';
	$http_options['content'] = $post_data;

	$options['http']=$http_options;

	$context = stream_context_create($options);
	$response = file_get_contents($url,false,$context);
	if ($debug) debug(['response'=>$response]);
	switch ($decode){
		case ARRAY_CONVERSION:
			return json_decode($response,true);
			break;
		case OBJECT_CONVERSION:
			return json_decode($response);
			break;
		case DOM_CONVERSION:
			$dom = new DOMDocument();
			$dom->loadHTML($response);
			return $dom;
	}
	return $response;
}

/**
 * checks if a user is logged in and forces a login of not.
 */
function require_login($service_name = null){
	global $user,$theme;
	if ($revoke = param('revoke')) die(revoke_token($revoke));
	if (empty($service_name)) throw new Exception('require_login called without a service name!');
	if (empty($_SESSION['token'])) redirect(getUrl('user','login?returnTo='.urlencode(location())));
	$user = getLocallyFromToken();

	if ($user === null) validateToken($service_name);
	if ($user === null) redirect(getUrl('user','login?returnTo='.urlencode(location())));
	if (!empty($user->theme)) $theme = $user->theme;
}

function revoke_token($token){
	$db = get_or_create_db();
	$query = $db->prepare('DELETE FROM tokens WHERE token = :token');
	if (!$query->execute([':token'=>$token])) throw new Exception('Was not able to execute DELETE statement.');
	unset($_SESSION['token']);
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

	return file_get_contents($url,false,$context);
}

function send_mail($sender, $reciever, $subject, $text, $attachment = null, $extra_headers = []){
	if (!is_array($reciever)) $reciever = [$reciever];

	$header  = "From: ".$sender."\r\n";
	$header .= "Sender: ".$sender."\r\n";
	$header .= "Reply-To: ".$sender."\r\n";
	if (!empty($extra_headers) && is_array($extra_headers)){
	    foreach ($extra_headers as $k => $v) $header .= $k.': '.$v."\r\n";
	}

	if ($attachment){
		$filename = $attachment['name'];

		$content = $attachment['content'];
		$content = chunk_split(base64_encode($content));
		$uid = md5(uniqid(time()));

		// header
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
	} else $nmessage = $text;
	
	//debug(['header' => $header,'message' => $nmessage],1);

	$good = true;
	foreach ($reciever as $rec) $good = $good & mail($rec, $subject, $nmessage, $header);
	return $good;
}

function send_mail_debug($sender, $receiver, $subject, $text, $attachment = null, $extra_headers = []){
	$msg = ['from'=>$sender,'to'=>$receiver,'subject'=>$subject];
	//$msg['text']=$text;
	if (!empty($attachment)) $msg['attach'] = $attachment;
	if (!empty($extra_headers)) $msg['extra-headers'] = $extra_headers;
	debug(['send mail'=>$msg]);
}

function t($text,$replacements=null){
	global $lang,$translations;
	$lang_file ='lang.'.$lang.'.php';
	if (file_exists($lang_file)){
		include $lang_file;
		if (is_array($text)) {
			$e = new Exception;
			error_log(var_export($e->getTraceAsString(), true));
		}
		if (isset($translations[$text])) $text=$translations[$text];
	}
	return replace_text($text,$replacements);
}

function task_state($state){
	switch ($state){
		case TASK_STATUS_OPEN: return 'open';
		case TASK_STATUS_PENDING: return 'pending';
		case TASK_STATUS_STARTED: return 'started';
		case TASK_STATUS_CANCELED: return 'canceled';
		case TASK_STATUS_COMPLETE : return 'completed';
	}
	return 'unknown';
}

function test_mail($reciever,$subject, $data, $head){
	debug(['action'=>'php::mail','reciever'=>$reciever,'subject'=>$subject,'data'=>$data,'head'=>$head]);
	return true;
}

function throw_exception($text,$replacements){
	throw new Exception(t($text,$replacements));
}

function transformUml($text){
    $startpos = strpos($text, '@startuml');
    while ($startpos !== false){
        $endpos = strpos($text, '@enduml',$startpos);
        if ($endpos === false) break;
        
        $uml = substr($text, $startpos, $endpos-$startpos+7)."\n";
        $tmpfname = "/tmp/".md5($uml);
        if (!file_exists($tmpfname.'.svg')){
            file_put_contents($tmpfname, $uml);
            exec('java -jar /opt/plantuml.jar -charset utf-8 -tsvg '.$tmpfname);
        }
        $image = '<img src="data:image/svg+xml;base64,'.base64_encode(file_get_contents($tmpfname.'.svg')).'"/>';
        $text = substr($text, 0,$startpos)."\n".$image.substr($text, $endpos+8);
        $startpos = strpos($text, '@startuml',$startpos + strlen($image));
    }
    $startpos = strpos($text, '@startgantt');
    while ($startpos !== false){
        $endpos = strpos($text, '@endgantt',$startpos);
        if ($endpos === false) break;
        
        $uml = substr($text, $startpos, $endpos-$startpos+8)."\n";
        $tmpfname = "/tmp/".md5($uml);
        if (!file_exists($tmpfname.'.svg')){
            file_put_contents($tmpfname, $uml);
            exec('java -jar /opt/plantuml.jar -charset utf-8 -tsvg '.$tmpfname);
        }
        $image = '<img src="data:image/svg+xml;base64,'.base64_encode(file_get_contents($tmpfname.'.svg')).'"/>';
        $text = substr($text, 0,$startpos)."\n".$image.substr($text, $endpos+9);
        $startpos = strpos($text, '@startgantt',$startpos + strlen($image));
    }
    return $text;
}

function url($uri){
	$parts = explode(':', $uri,2);
	$module = array_shift($parts);
	$id = array_shift($parts);
	//debug(['module'=>$module,'id'=>$id],1);
	switch ($module){
		case 'files':
			return getUrl($module,'?path='.$id.'&'.$param);
			break;
		case 'model':
		case 'time':
			if (strpos($id,'project:')===0) return getUrl($module,'?'.str_replace(':', '=', $id));
			break;
		case 'poll':
			return getUrl($module,'view?id='.$id.'&'.$param);
			break;
	}
	return getUrl($module,$id.'/view');
}

/* uses the user service to validate the session token and get user data */
function validateToken($service_name = null){
	global $user;

	$token = $_SESSION['token'];
	$user = request('user', 'validateToken',['token'=>$token,'domain'=>getUrl($service_name)],false,OBJECT_CONVERSION);

	if (is_object($user)){
		$token = $user->token;
		unset($user->token);
		$user_data = json_encode($user);
		$db = get_or_create_db();
		$params = [':token'=>$token->token,':user_data'=>$user_data,':exp'=>$token->expiration];
		$query = $db->prepare('INSERT OR IGNORE INTO tokens (token, user_data, expiration) VALUES (:token, :user_data, :exp);');
		if (!$query->execute($params)) throw new Exception('Was not able to store token in database.');
	} else {
		$user = null;
		revoke_token($token);
	}
}

function warn($message,$args = null){
	if ($message === null) return;
	$message = t($message,$args);
	$_SESSION['warnings'][crc32($message)] = $message;
}

class UmbrellaObject{
	function patch($data = []){
		if (!isset($this->dirty)) $this->dirty = [];
		foreach ($data as $key => $val){
			if (!isset($this->{$key}) || $this->{$key} != $val) $this->dirty[] = $key;
			$this->{$key} = $val;
		}
		return $this;
	}
}

class UmbrellaObjectWithId extends UmbrellaObject{
	function patch($data = []){
		if (!isset($this->dirty)) $this->dirty = [];
		foreach ($data as $key => $val){
			if ($key === 'id' && isset($this->id)) continue; // don't patch the id!
			if (!isset($this->{$key}) || $this->{$key} != $val) $this->dirty[] = $key;
			$this->{$key} = $val;
		}
		return $this;
	}
}

assert_options(ASSERT_ACTIVE,   true);
assert_options(ASSERT_BAIL,     false);
assert_options(ASSERT_WARNING,  true);
assert_options(ASSERT_CALLBACK, 'assert_failure');

init();
