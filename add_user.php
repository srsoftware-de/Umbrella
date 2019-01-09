<?php include 'controller.php';
require_login('task');

if ($task_id = param('id')){
	$task = Task::load(['ids'=>$task_id]);

	$title = $task->name.' - Umbrella';

	if (!$task->is_writable()){
		error('You are not allowed to edit the user list of this task!');
		redirect('view');
	}

	$project_users = $task->project()['users'];
	foreach ($task->users() as $uid => $u) unset($project_users[$uid]);

	if (empty($project_users)) {
		warn('All members of this project are already assigned to this task.');
		redirect('view');
	}

	if ($users = param('users')){
		$users = array_intersect_key($users,$project_users); // only users of the project may be added to the task
		foreach ($users as $uid => $perm){
			$u = $project_users[$uid]['data'];
			$u['permission'] = $perm;
			$task->add_user($u);
		}
		redirect('view');
	}
} else {
	error('No task id passed to add user!');
	redirect(getUrl('task'));
}

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include '../common_templates/messages.php';

if ($task->is_writable()){ ?>
<h1><?= t('Add user to "?"',$task->name) ?></h1>
<form method="POST">
	<fieldset>
		<legend><?= t('Add user to task') ?></legend>
		<table>
			<tr>
				<th><?= t('User')?></th>
				<th title="<?= t('read + write')?>"><?= t('R/W')?></th>
				<th title="<?= t('read only')?>"><?= t('R')?></th>
				<th title="<?= t('no access')?>">–</th>
			</tr>
			<?php foreach ($project_users as $id => $u) {
				$perm = isset($task->users[$id]) ? $task->users[$id]['permissions'] : 0;
			?>
			<tr>
				<td><?= $u['data']['login']?></td>
				<td><input type="radio" name="users[<?= $id ?>]" title="<?= t('read + write')?>" value="<?= TASK_PERMISSION_READ_WRITE ?>" <?= $perm == TASK_PERMISSION_READ_WRITE ? 'checked="checked"':'' ?>/></td>
				<td><input type="radio" name="users[<?= $id ?>]" title="<?= t('read only')?>"    value="<?= TASK_PERMISSION_READ ?>" <?= $perm == TASK_PERMISSION_READ ? 'checked="checked"':'' ?>/></td>
				<td><input type="radio" name="users[<?= $id ?>]" title="<?= t('no access')?>"    value="0" <?= $perm == 0 ? 'checked="checked"':'' ?>/></td>
			</tr>
			<?php } ?>
		</table>
		<p>
			<input type="checkbox" name="notify" checked="checked"><?= t('notify user') ?>
		</p>
		<button type="submit"><?= t('add user') ?></button>
	</fieldset>
</form>
<?php }
include '../common_templates/closure.php'; ?>
