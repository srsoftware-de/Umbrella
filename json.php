<?php 

include '../bootstrap.php';
include 'controller.php';

require_login('task');
if ($task_ids = param('ids')){
	$task_ids = explode(',', $task_ids);
	die(json_encode(get_tasks(['ids'=>$task_ids])));
}

$task_id = param('id');
if (!$task_id) error('No task id passed to view!');

die(json_encode(get_tasks(['id'=>$task_id])));
