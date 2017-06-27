<?php 

include '../bootstrap.php';
include 'controller.php';

require_login();
$task_id = param('id');

if (!$task_id) error('No task id passed to view!');

echo json_encode(load_task($task_id));
