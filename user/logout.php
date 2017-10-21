<?php

include '../bootstrap.php';
include 'controller.php';

if ($token){
	require_login();
	revoke_token(	);
}
redirect(param('returnTo','login'));

?>
