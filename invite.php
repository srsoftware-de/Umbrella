<?php

include '../bootstrap.php';
include 'controller.php';

require_user_login();

$user_id = param('id');

if ($user->id != 1 && $user_id != $user->id){
	error('Currently, only admin can invite other users!');
} elseif ($user_id === null){
	error('No user id given!');
} else {
	$u = load_user($user_id);
	invite_user($u);	
}

redirect('../index');