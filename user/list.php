<?php $title = 'Umbrella Users';

include '../bootstrap.php';
include 'controller.php';

require_user_login();
$ids = param('ids');
if ($ids && !is_array($ids)) $ids = explode(',',$ids);
echo json_encode(get_userlist($ids));