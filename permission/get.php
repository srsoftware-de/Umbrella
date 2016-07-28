<?php
function getUrl($path){
	$proto = 'http';
	if ($_SERVER['HTTPS'] == 'on') $proto.='s';
	$proto.='://';
	
	$host = $_SERVER['HTTP_HOST'];
	$script = dirname($_SERVER['SCRIPT_NAME']);
	
	return $proto.$host.$script.'/'.$path;  
}

function request($path){
	$url = getUrl($path);
	echo $url.'<br/>';
	return file_get_contents($url);
}

if (!isset($_GET['token'])) die(NULL);
$token = $_GET['token'];
if ($token == null) die(NULL);

die(request('user/validate?token='.$token));

?>
