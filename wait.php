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

$problems = [];
if (!empty($task->start_date) && time() > strtotime($task->start_date)){
	$problems[] = t('The start date (?) of this task has already passed.',$task->start_date);
	$problems[] = t('In order to set this task in "?" state, the <b>start date</b> has to be <b>removed</b>.',t('wait'));
	$task->patch(['start_date'=>null]);
}
if (empty($problems) || param('confirm','no')=='yes'){
	if (in_array('start_date',$task->dirty)) $task->save(); // update start date
	$task->set_state(TASK_STATUS_PENDING);
	if (empty($task->parent_task_id)) redirect(getUrl('task',$task->id.'/view'));
	redirect(getUrl('task',$task->parent_task_id.'/view'));
}

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<fieldset>
	<legend><?= t('Problems')?></legend>
	<ul>
	<?php foreach ($problems as $problem) { ?>
		<li><?= $problem ?></li>
	<?php } // foreach ?>
	</ul>
	<a class="button" href="?confirm=yes"><?= t('Confirm')?></a>
	<a class="button" href="<?= getUrl('task',$task_id.'/view') ?>"><?= t('Abort')?></a>
</fieldset>

<?php include '../common_templates/closure.php';?>