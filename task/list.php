<?php $title = 'Umbrella Users';

include '../bootstrap.php';
include 'controller.php';

require_login();

die(json_encode(get_task_list(param('order'),param('project'))));