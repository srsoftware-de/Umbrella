<?php

include '../bootstrap.php';
include 'controller.php';

require_user_login();
$user_id = param('id');

if ($user_id == $user->id || $user->id == 1) {
	$u = lock_user($user_id);
} else {
	warn('Currently, only admin can lock other users!');
}
redirect('../index');
