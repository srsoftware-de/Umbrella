<?php $title = 'Umbrella Users';

include '../bootstrap.php';
include 'controller.php';

require_login();
echo json_encode(get_userlist());