<?php 

include '../bootstrap.php';
include 'controller.php';

require_login('task');
$task_id = param('id');
if ($task_ids = param('ids')){
	$task_ids = explode(',', $task_ids);
	die(json_encode(load_tasks($task_ids)));
}

if (!$task_id) error('No task id passed to view!');

die(json_encode(load_tasks($task_id)));
