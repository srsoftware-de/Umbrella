<?php $title = 'Umbrella Task Management';

include '../bootstrap.php';
include 'controller.php';
require_login('task');

$task_id = param('id');
if (!$task_id) error('No task id passed!');

$user_id = param('uid');
if (!$user_id) error('No user id passed!');

$task = get_tasks(['id'=>$task_id]);
remove_user_from_task($user_id,$task['id']);
redirect('view');
