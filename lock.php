<?php include 'controller.php';

$user = User::require_login();

if ($user_id = param('id')){
	if ($user_id == $user->id || $user->id == 1) {
		User::load(['ids'=>$user_id])->lock();
	} else {
		warn('Currently, only admin can lock other users!');
	}
} else error('No user id given!');

redirect('../index');
