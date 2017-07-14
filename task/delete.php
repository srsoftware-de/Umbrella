<?php $title = 'Umbrella Task Management';

include '../bootstrap.php';
include 'controller.php';
require_login();

$task_id = param('id');
if (!$task_id) error('No task id passed!');
$task = load_task($task_id);
$target = param('redirect');
if (param('confirm')=='yes'){
	delete_task($task_id);
	if ($target){
		redirect($target);
	} elseif ($task['parent_task_id']){
		redirect('../'.$task['parent_task_id'].'/view');
	}
	redirect('../../project/'.$task['project_id'].'/view');
}

include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<h2>This will remove the task "<?= $task['name']?>". Are you sure?</h1>
<a href="?confirm=yes<?= $target?('&redirect='.$target):''?>" class="button">Yes</a>
<a href="view" class="button">No</a>
