<?php include 'controller.php';
require_login('task');

$project_id = param('id');
if (empty($project_id)){
	error('No project id passed!');
	redirect(getUrl('project'));
}

$project = request('project','json',['ids'=>$project_id,'users'=>'true']);
if (empty($project)){
	error('You don`t have access to that project!');
	redirect(getUrl('project'));
}

$name = post('name');
$user_permissions = param('users');

if ($name){
	if (!empty($user_permissions) && is_array($user_permissions)){
		$users = [];
		foreach ($project['users'] as $uid => $entry){
			$u = $entry['data'];
			$perm = $uid == $user->id ? TASK_PERMISSION_OWNER : $user_permissions[$uid];
			if ($perm == 0) continue;
			$u['permission'] = $perm;
			$users[$uid] = $u;
		}
		$task = new Task();
		if ($task->patch($_POST)->patch(['project_id'=>$project_id,'users'=>$users])->save()) redirect(getUrl('task',$task->id.'/view'));
	} else error('Selection of at least one user is required!');
}

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>
<form method="POST">
	<fieldset>
		<legend><?= t('Create new task')?></legend>
		<fieldset>
			<legend><?= t('Project')?></legend>
			<a href="<?= getUrl('project',$project_id.'/view')?>"><?= $project['name']?></a>
			&nbsp;&nbsp;&nbsp;&nbsp;
			<a href="<?= getUrl('files').'?path=project/'.$project_id ?>" class="symbol" title="show project files" target="_blank"></a>
		</fieldset>
		<fieldset><legend><?= t('Name')?></legend>
			<input type="text" name="name" value="<?= $name ?>" autofocus="true"/>
		</fieldset>
		<fieldset>
			<legend><?= t('Description - <a target="_blank" href="?">Markdown supported ↗cheat sheet</a>',t('MARKDOWN_HELP'))?></legend>
			<textarea name="description"><?= param('description'); ?></textarea>
		</fieldset>
		<fieldset>
			<legend><?= t('Estimated time')?></legend>
			<label>
				<?= t('? hours','<input type="number" name="est_time" value="'.param('est_time').'" />')?>
			</label>
		</fieldset>
		<fieldset>
			<legend><?= t('Permissions') ?></legend>
			<table>
				<tr>
					<th><?= t('User')?></th>
					<th title="<?= t('read + write')?>" class="symbol"></th>
					<th title="<?= t('read only')?>" class="symbol"></th>
					<th title="<?= t('no access')?>" class="symbol"></th>
				</tr>
			<?php foreach ($project['users'] as $id => $u) {
				$owner = $id == $user->id;
				?>
				<tr>
					<td><?= $u['data']['login']?></td>
					<td><input type="radio" name="users[<?= $id ?>]" title="<?= t('read + write')?>" value="<?= TASK_PERMISSION_READ_WRITE ?>" <?= $owner?'checked="checked"':'' ?>/></td>
					<td><input type="radio" name="users[<?= $id ?>]" title="<?= t('read only')?>" value="<?= TASK_PERMISSION_READ ?>" /></td>
					<td><input type="radio" name="users[<?= $id ?>]" title="<?= t('no access')?>" value="0" <?= $owner?'':'checked="checked"' ?>/></td>
				</tr>
			<?php } ?>
			</table>
			<label>
				<input type="checkbox" name="notify" checked="true" />
				<?= t('notify users') ?>
			</label>
			<p>
			<?= t('Only selected users will be able to access the task!') ?>
			</p>
		</fieldset>
		<?php if (isset($services['bookmark'])){?>
		<fieldset><legend><?= t('Tags')?></legend>
			<input name="tags" type="text" value="<?= param('tags') ?>" />
		</fieldset>
		<?php }?>
		<fieldset>
			<legend><?= t('Start date')?></legend>
			<input name="start_date" type="date" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" value="<?= param('start_date',date('Y-m-d')); ?>" />
		</fieldset>
		<fieldset>
			<legend><?= t('Due date')?></legend>
			<input name="due_date" type="date" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" value="<?= param('due_date') ?>" />
		</fieldset>
		<button type="submit"><?= t('Save task') ?></button>
	</fieldset>
</form>

<?php include '../common_templates/closure.php'; ?>
