<?php include 'controller.php';

$user = User::require_login();
$user->patch(['last_logoff'=>time()])->save();

Token::load($_SESSION['token'])->revoke()->destroy();
session_destroy();
redirect(param('returnTo',getUrl('user','login')));

?>
