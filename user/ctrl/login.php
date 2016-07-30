<?php
if (!isset($_GET['username'])) die(NULL);
if (!isset($_GET['password'])) die(NULL);
$username = $_GET['username'];
$password = $_GET['password'];

$userlist = json_decode(file_get_contents('.userlist'),true);

if ($userlist[$username] == $password){
	$token = '"asdfghjkl"';
	die($token);
}
die(NULL);

?>
