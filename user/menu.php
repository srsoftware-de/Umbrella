<?php

include 'bootstrap.php';

if ($token == null) die(NULL);

$perms = request('permission','get?token='.$token);
if (!is_array($perms)) die(NULL);

if (!is_array($perms['user'])) die(NULL);

$permission_entries = array('add'  => array('add' => 'add new user'),
			    'list' => array('list' => 'list all users'));

$entries = array();
foreach ($perms['user'] as $permission){
	if (array_key_exists($permission,$permission_entries)){
		$entries[] = $permission_entries[$permission];
	}
}
die(json_encode($entries));
