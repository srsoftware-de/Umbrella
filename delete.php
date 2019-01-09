<?php include 'controller.php';
require_login('task');

if ($task_id = param('id')) {
	$task = Task::load(['ids'=>$task_id]);
	if ($task->is_writable()){
		$target = param('redirect');
		if (param('confirm')=='yes'){
			$task->delete();
			if ($target){
				redirect($target);
			} elseif ($task->parent_task_id){
				redirect('../'.$task->parent_task_id.'/view');
			}
			redirect('../../project/'.$task->project_id.'/view');
		}
	} else {
		error('Task does not exist or you are not allowed to access it.');
	}

} else {
	error('No task id passed!');
	redirect(getUrl('task'));
}

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include '../common_templates/messages.php';

if ($task){ ?>
<fieldset>
	<legend><?= t('This will remove the task "?". Are you sure?',$task->name)?></legend>
	<a href="?confirm=yes<?= $target?('&redirect='.$target):''?>" class="button"><?= t('Yes')?></a>
	<a href="view" class="button"><?= t('No')?></a>
</fieldset>
<?php }

include '../common_templates/closure.php';?>