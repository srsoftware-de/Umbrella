<?php $title = 'Umbrella Users';

include '../bootstrap.php';
include 'controller.php';

$user = current_user();
die(json_encode(get_task_list(param('order'),param('project'))));