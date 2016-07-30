<?php

include '../bootstrap.php';

if ($token == null) die(NULL);

if ($token == 'asdfghjkl'){
	$validity = date('U')+3600;
	$uid = 1;
	$response = array('validity' => date('U')+3600,
			  'uid' => 1);
	die(json_encode($response));
}
die(NULL);

?>
