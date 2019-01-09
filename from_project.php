<?php include 'controller.php';
require_login('task');

if ($project_id = param('id')){
	$all_projects = request('project','json');
	if ($target_pid = param('target_project')){
		$source = request('project','json',['ids'=>$project_id,'users'=>true]);
		$users = [];
		foreach ($source['users'] as $uid => $dummy) $users[$uid] = ['id'=>$uid, 'permission'=>TASK_PERMISSION_READ_WRITE];
		$task = new Task();
		$task->patch(['name'=>$source['name'],'description'=>$source['description'],'project_id'=>$target_pid,'users'=>$users])->save();

		$tasks_of_source = Task::load(['project_ids'=>$project_id]);
		foreach ($tasks_of_source as $depending_task){
			$depending_task->patch(['project_id'=>$target_pid]);
			if (empty($depending_task->parent_task_id)) $depending_task->patch(['parent_task_id' => $task->id]);
			$depending_task->save();
		}
		if (isset($services['notes'])) request('notes','project:'.$project_id.'/update_uri?new=task:'.$task->id);
		request('project','cancel',['id'=>$project_id]);
		redirect(getUrl('task',$task->id.'/view'));
	}
} else {
	error('No project id passed!');
	redirect(getUrl('task'));
}

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>

<fieldset>
	<legend><?= t('Convert project "?" to task',$all_projects[$project_id]['name'])?></legend>
	<p><?= t('This will convert the project to a task, which will then be added to the project selected below:')?></p>
	<form method="POST">
	<select name="target_project">
		<option value="">== <?= t('Target project')?> ==</option>
		<?php foreach ($all_projects as $pid => $p){ if ($pid == $project_id) continue; ?>
		<option value="<?= $pid ?>"><?= $p['name'] ?></option>
		<?php }?>
	</select>
	<button type="submit"><?= t('Convert')?></button>
	</form>
</fieldset>
<?php include '../common_templates/closure.php'; ?>

