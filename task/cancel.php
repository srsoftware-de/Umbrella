<?php $title = 'Umbrella Task Management';

include '../bootstrap.php';
include 'controller.php';
require_login();

$task_id = param('id');
if (!$task_id) error('No task id passed!');
set_task_state($task_id,TASK_STATUS_CANCELED);
redirect('view');