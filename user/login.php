<?php
if (!isset($_GET['username'])) die(NULL);
if (!isset($_GET['password'])) die(NULL);
$username = $_GET['username'];
$password = $_GET['password'];
if ($username == 'srichter'){
	$token = '"asdfghjkl"';
	die($token);
}
die(NULL);

?>
