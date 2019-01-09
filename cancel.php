<?php include 'controller.php';
require_login('task');

$task_id = param('id');
if (!$task_id) error('No task id passed!');
set_task_state($task_id,TASK_STATUS_CANCELED);
if ($target = param('redirect')) {
	redirect($target);
} else {
	redirect('view');
}
