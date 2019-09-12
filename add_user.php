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

$title = $task->name.' - Umbrella';

if (!$task->is_writable()){
	error('You are not allowed to edit the user list of this task!');
	redirect(getUrl('task',$task_id.'/view'));
}

$project_users = $task->project('users');
if (empty($project_users)) {
	warn('All members of this project are already assigned to this task.');
	redirect(getUrl('task',$task_id.'/view'));
}

$users = param('users');
if (!empty($users) && is_array($users)){
	$notify = param('notify') == 'on';
	$added = false;
	foreach ($users as $uid => $perm){
		if (!array_key_exists($uid, $project_users)){
			error('User with id ◊ is not part of the project!',$uid);
			continue;
		}
		if ($perm == Task::PERMISSION_CREATOR) $perm = Task::PERMISSION_READ_WRITE; // if someone tries to assign creator permissions to a task: fall back to read/write
		$u = $project_users[$uid]['data'];
		$u['permission'] = $perm;
		if ($task->add_user($u,$notify)) $added = true;
	}
	if ($added) redirect(getUrl('task',$task_id.'/view'));
}

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include '../common_templates/messages.php';

if ($task->is_writable()){ ?>
<form method="POST">
	<fieldset>
		<legend><?= t('Add user to task "◊"','<a href="view">'.$task->name.'</a>') ?></legend>
		<table>
			<tr>
				<th><?= t('User')?></th>
				<th class="symbol" title="<?= t('assignee')?>"></th>
				<th class="symbol" title="<?= t('read + write')?>"></th>
				<th class="symbol" title="<?= t('read only')?>"></th>
				<th class="symbol" title="<?= t('no access')?>"></th>
			</tr>
			<?php foreach ($project_users as $id => $u) {
				$perm = isset($task->users[$id]) ? $task->users[$id]['permissions'] : 0;
				$disabled = ($perm == Task::PERMISSION_CREATOR);
			?>
			<tr>
			</tr>
			<tr>
				<td><?= $u['data']['login']?></td>
				<td><input type="radio" name="users[<?= $id ?>]"<?= $disabled ? ' readonly="readonly"':''?> title="<?= t('assignee')?>" value="<?= Task::PERMISSION_ASSIGNEE ?>" <?= $perm == Task::PERMISSION_ASSIGNEE ? 'checked="checked" ':'' ?>/></td>
				<td><input type="radio" name="users[<?= $id ?>]"<?= $disabled ? ' readonly="readonly"':''?> title="<?= t('read + write')?>" value="<?= Task::PERMISSION_READ_WRITE ?>" <?= $perm == Task::PERMISSION_READ_WRITE||$perm == Task::PERMISSION_CREATOR ? 'checked="checked" ':'' ?>/></td>
				<td><input type="radio" name="users[<?= $id ?>]"<?= $disabled ? ' readonly="readonly"':''?> title="<?= t('read only')?>" value="<?= Task::PERMISSION_READ ?>" <?= $perm == Task::PERMISSION_READ ? 'checked="checked"':'' ?>/></td>
				<td><input type="radio" name="users[<?= $id ?>]"<?= $disabled ? ' readonly="readonly"':''?> title="<?= t('no access')?>" value="0" <?= $perm == 0 ? 'checked="checked" ':'' ?>/></td>
			</tr>
			<?php } ?>
		</table>
		<p>
			<label>
				<input type="checkbox" name="notify" checked="checked"> <?= t('notify user') ?>
			</label>
		</p>
		<button type="submit"><?= t('add user') ?></button>
	</fieldset>
</form>
<?php }
include '../common_templates/closure.php'; ?>
