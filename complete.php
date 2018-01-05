<?php $title = 'Umbrella Task Management';

include '../bootstrap.php';
include 'controller.php';
require_login('task');
$task_id = param('id');
if (!$task_id) error('No task id passed!');
set_task_state($task_id,TASK_STATUS_COMPLETE);
if ($target = param('redirect')) {
	redirect($target);
} else {
	$task = load_tasks(['ids'=>$task_id]);
	if (isset($task['parent_task_id']) && $task['parent_task_id']!==null) redirect(getUrl('task',$task['parent_task_id'].'/view'));
	redirect(getUrl('project',$task['project_id'].'/view'));
}
