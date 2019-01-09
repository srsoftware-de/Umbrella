<?php include 'controller.php';
require_login('task');

$task_id = param('id');
if (!$task_id) error('No task id passed!');

$user_id = param('uid');
if (!$user_id) error('No user id passed!');

$task = load_tasks(['ids'=>$task_id]);
remove_user_from_task($user_id,$task['id']);
redirect('view');
