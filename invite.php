<?php include 'controller.php';

$user = User::require_login();

if ($user_id = param('id')){
	if ($user->id != 1 && $user_id != $user->id){
		error('Currently, only admin can invite other users!');
	} else User::load(['ids'=>$user_id])->invite();
} else error('No user id given!');

redirect('../index');