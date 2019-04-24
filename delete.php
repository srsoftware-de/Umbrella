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
	redirect(getUrl('task',$task->id.'/view'));
}


if (param('confirm')=='yes'){
	$task->delete();
	if ($target = param('redirect')) redirect($target);
	if ($task->parent_task_id) redirect(getUrl('task',$task->parent_task_id.'/view'));
	redirect(getUrl('project',$task->project_id.'/view'));
}

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include '../common_templates/messages.php';

if ($task){ ?>
<fieldset>
	<legend><?= t('This will remove the task "◊". Are you sure?',$task->name)?></legend>
	<a href="?confirm=yes<?= $target?('&redirect='.$target):''?>" class="button"><?= t('Yes')?></a>
	<a href="view" class="button"><?= t('No')?></a>
</fieldset>
<?php }

include '../common_templates/closure.php';?>