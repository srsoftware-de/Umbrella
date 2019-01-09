<?php include 'controller.php';
require_login('task');

$redirect = 'view';
if ($task_id = param('id')){
	$task = Task::load(['ids'=>$task_id]);
	$task->set_state(TASK_STATUS_CANCELED);
	if (!empty($task->parent_task_id)) $redirect=getUrl('task',$task->parent_task_id.'/view');
} else error('No task id passed!');

redirect(param('redirect',$redirect));