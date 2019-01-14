<?php include 'controller.php';

user_revoke_token();
session_destroy();
redirect(param('returnTo','login'));

?>
