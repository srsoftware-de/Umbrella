<?php include 'controller.php';

Token::load($_SESSION['token'])->revoke()->destroy();
session_destroy();
redirect(param('returnTo',getUrl('user','login')));

?>
