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

if (!$task->is_writable()){
	error('You are not allowed to modify this task.');
	redirect(getUrl('task',$task_id.'/view'));
}

$user_id = param('uid');
if (empty($user_id)){
	error('No user id passed!');
	redirect(getUrl('task',$task_id.'/view'));
}

$task->drop_user($user_id);
redirect(getUrl('task',$task_id.'/view'));

