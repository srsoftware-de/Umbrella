<?php

include '../bootstrap.php';

if ($token == null) die(NULL);

$response = request('user','ctrl/validate');

if (!isset($response['validity'])) die(NULL);
if (!isset($response['uid'])) die(NULL);
if (date('U') > $response['validity']) die(NULL);

$permissions = array();
if ($response['uid'] == 1) {
	$permissions['user'] = array('add','list');
	$permissions['project'] = array('add','list');
}
die(json_encode($permissions));

?>
