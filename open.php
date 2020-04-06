<?php include 'controller.php';
require_login('task');

$task_id = param('id');
if (empty($task_id)){
	error('No task id passed!');
	redirect(getUrl('task'));
}

$task = Task::load(['ids'=>$task_id]);
if (empty($task)){
	error('You don`t have access to that task!');
	redirect(getUrl('task'));
}

$task->set_state(TASK_STATUS_OPEN);
$redirect = getUrl('task',(empty($task->parent_task_id) ? $task->id : $task->parent_task_id).'/view');
redirect(param('redirect',$redirect));