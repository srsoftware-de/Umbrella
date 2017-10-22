<?php

include '../bootstrap.php';
include 'controller.php';

user_revoke_token();
redirect(param('returnTo','login'));

?>
