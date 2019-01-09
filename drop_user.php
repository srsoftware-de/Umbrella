<?php include 'controller.php';
require_login('task');

if ($task_id = param('id')){
	if ($user_id = param('uid')){
		Task::load(['ids'=>$task_id])->drop_user($user_id);
	} else error('No user id passed!');
} else error('No task id passed!');
redirect('view');
