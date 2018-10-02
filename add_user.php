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

foreach ($project_users as $uid => &$usr) $usr['permission'] = $project_permissions[$uid]['permissions'];

load_users($task,$project_users); // add users to task

$title = $task['name'].' - Umbrella';

$allowed = isset($task['users'][$user->id]['permissions'])
        && in_array($task['users'][$user->id]['permissions'], [TASK_PERMISSION_OWNER,TASK_PERMISSION_READ_WRITE]);

if (!$allowed){
	error('You are not allowed to edit the user list of this task!');
	redirect('view');
}

if (empty($project_users)) {
	warn('All members of this project are already assigned to this task.');
	redirect('view');
}

if ($users = param('users')){
	$users = array_intersect_key($users,$project_users); // only users of the project may be added to the task
	foreach ($users as $uid => $perm){
		$u = $project_users[$uid];
		$u['permission'] = $perm;
		add_user_to_task($task,$u);
	}
	redirect('view');
}

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include '../common_templates/messages.php';

if ($allowed){ ?>
<h1><?= t('Add user to "?"',$task['name']) ?></h1>
<form method="POST">
	<fieldset>
		<legend><?= t('Add user to task') ?></legend>
		<table>
			<tr>
				<th><?= t('User')?></th>
				<th title="<?= t('owner')?>"><?= t('owner')?></th>
				<th title="<?= t('read + write')?>"><?= t('R/W')?></th>
				<th title="<?= t('read only')?>"><?= t('R')?></th>
				<th title="<?= t('no access')?>">–</th>
			</tr>
			<?php foreach ($project_users as $id => $u) { 
				$perm = isset($task['users'][$id]) ? $task['users'][$id]['permissions'] : 0; 
			?>
			<tr>
				<td><?= $u['login']?></td>
				<td><input type="radio" name="users[<?= $id ?>]" title="<?= t('owner')?>" value="<?= TASK_PERMISSION_OWNER ?>" <?= $perm == TASK_PERMISSION_OWNER ? 'checked="checked"':'' ?>/></td>
				<td><input type="radio" name="users[<?= $id ?>]" title="<?= t('read + write')?>" value="<?= TASK_PERMISSION_READ_WRITE ?>" <?= $perm == TASK_PERMISSION_READ_WRITE ? 'checked="checked"':'' ?>/></td>
				<td><input type="radio" name="users[<?= $id ?>]" title="<?= t('read only')?>"    value="<?= TASK_PERMISSION_READ ?>" <?= $perm == TASK_PERMISSION_READ ? 'checked="checked"':'' ?>/></td>
				<td><input type="radio" name="users[<?= $id ?>]" title="<?= t('no access')?>"    value="0" <?= $perm == 0 ? 'checked="checked"':'' ?>/></td>
			</tr>
			<?php } ?>
		</table>
		<p>
			<input type="checkbox" name="notify" checked="true"><?= t('notify user') ?>
		</p>
		<button type="submit"><?= t('add user') ?></button>
	</fieldset>
</form>
<?php }
include '../common_templates/closure.php'; ?>
