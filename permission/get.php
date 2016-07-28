<?php

include 'bootstrap.php';

if ($token == null) die(NULL);

$response = request('user','validate?token='.$token);

if (!isset($response['validity'])) die(NULL);
if (!isset($response['uid'])) die(NULL);
if (date('U') > $response['validity']) die(NULL);

$permissions = array();
if ($response['uid'] == 1) {
	$permissions['user'] = array('add','list');
}
die(json_encode($permissions));

?>
