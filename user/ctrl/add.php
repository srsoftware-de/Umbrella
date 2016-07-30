<?php

include '../bootstrap.php';

$perms = request('permission','ctrl/get');
if (!is_array($perms)) die(NULL);

if (!is_array($perms['user'])) die(NULL);
if (!in_array('add', $perms['user'])) die(NULL);

if (!isset($_GET['username'])) die(NULL);
if (!isset($_GET['password'])) die(NULL);
$username = $_GET['username'];
$password = $_GET['password'];

$userlist = json_decode(file_get_contents('.userlist'),true);
if (!is_array($userlist)) $userlist=array();
if (array_key_exists($username, $userlist)) die('User already exists');
$userlist[$username] = $password;

file_put_contents('.userlist', json_encode($userlist));

header('Location: ../..?token='.$token);
?>
