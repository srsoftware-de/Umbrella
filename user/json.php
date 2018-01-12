<?php $title = 'Umbrella Users';

include '../bootstrap.php';
include 'controller.php';

require_user_login();

$ids = param('ids');

die(json_encode(get_userlist($ids)));