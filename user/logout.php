<?php

include '../bootstrap.php';
include 'controller.php';

if ($token){
	$user = current_user();
	unset_token_cookie($user);
}
redirect(param('returnTo','index'));

?>
