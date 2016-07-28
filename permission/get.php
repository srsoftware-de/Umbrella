<?php

if (!isset($_GET['token'])) die(NULL);
$token = $_GET['token'];

if ($token == 'asdfghjkl'){
	$permission='user.add';
	die($permission);
}
die(NULL);

?>
