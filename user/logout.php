<?php

include '../bootstrap.php';
include 'controller.php';

revoke_token();
redirect(param('returnTo','login'));

?>
