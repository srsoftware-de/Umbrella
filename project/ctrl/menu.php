<?php

include '../bootstrap.php';

if ($token == null) die('no token given');

$perms = request('permission','ctrl/get');
if (!is_array($perms)) die(NULL);

if (!is_array($perms['project'])) die(NULL);

/* map from permission to ( action => description ) */
$permission_entries = array('add'  => array('add' => 'add new project'),
			    'list' => array('list' => 'list all projects'));

$entries = array();
foreach ($perms['project'] as $permission){
	if (array_key_exists($permission,$permission_entries)){
		foreach ($permission_entries[$permission] as $action => $title){
			$entries[$action] = $title;
		}
	}
}
die(json_encode($entries));
