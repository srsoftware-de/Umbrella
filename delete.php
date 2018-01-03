<?php $title = 'Umbrella Task Management';

include '../bootstrap.php';
include 'controller.php';
require_login('task');

$task_id = param('id');
if (!$task_id) error('No task id passed!');
$task = load_tasks(['ids'=>$task_id]);
if ($task){
	$target = param('redirect');
	if (param('confirm')=='yes'){
		delete_task($task);
		if ($target){
			redirect($target);
		} elseif ($task['parent_task_id']){
			redirect('../'.$task['parent_task_id'].'/view');
		}
		redirect('../../project/'.$task['project_id'].'/view');
	}
} else {
	error('Task does not exist or you are not allowed to access it.');	
}

include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<h2><?= t('This will remove the task "?". Are you sure?',$task['name'])?></h2>
<a href="?confirm=yes<?= $target?('&redirect='.$target):''?>" class="button"><?= t('Yes')?></a>
<a href="view" class="button"><?= t('No')?></a>

<?php include '../common_templates/closure.php';?>