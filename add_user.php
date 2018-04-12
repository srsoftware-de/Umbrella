<?php

include '../bootstrap.php';
include 'controller.php';

require_login('task');

$task_id = param('id');
if (!$task_id){
	error('No task id passed to add user!');
	redirect(getUrl('task'));
}
$task = load_tasks(['ids'=>$task_id]);

// get a map from user ids to permissions
$project_permissions = request('project','json',['ids'=>$task['project_id'],'users'=>'only']);

// load user data
$project_users = request('user','json',['ids'=>array_keys($project_permissions)]);
load_users($task,$project_users); // add users to task

foreach ($task['users'] as $uid => $task_user) unset($project_users[$uid]);

$title = $task['name'].' - Umbrella';

$allowed = isset($task['users'][$user->id]['permissions'])
        && in_array($task['users'][$user->id]['permissions'], [TASK_PERMISSION_OWNER,TASK_PERMISSION_PARTICIPANT]);

if (!$allowed){
	error('You are not allowed to edit the user list of this task!');
	redirect('view');
}

if (empty($project_users)) {
	warn('All members of this project are already assigned to this task.');
	redirect('view');
}

if ($new_user = param('new_user')){
	if (array_key_exists($new_user, $project_users)){ // only users of the project may be added to the task
		add_user_to_task($task,$project_users[$new_user]);
		redirect('view');
	} else {
		error('You are not allowed to add users to this task, who are not member of the project.');
	}
}

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include '../common_templates/messages.php';

if ($allowed){ ?>
<h1><?= t('Add user to "?"',$task['name']) ?></h1>
<form method="POST">
	<fieldset><legend><?= t('Add user to task') ?></legend>
		<fieldset>
			<select name="new_user">
				<?php if (count($project_users)>1) { ?>
					<option value="" selected="true"><?= t('= Select a user =') ?>'</option>
				<?php }
					foreach ($project_users as $id => $u){ ?>
				<option value="<?= $id ?>"><?= $u['login']?></option>
				<?php }?>
			</select>
			<label>
			<input type="checkbox" name="notify" checked="true"><?= t('notify user') ?>
			</label>
		</fieldset>
		<button type="submit"><?= t('add user') ?></button>
	</fieldset>
</form>
<?php }
include '../common_templates/closure.php'; ?>
