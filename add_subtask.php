<?php include 'controller.php';
require_login('task');

$task_id = param('id');
if (empty($task_id)){
	error('No parent task id passed!');
	redirect(getUrl('project'));
}

$task = Task::load(['ids'=>$task_id]);
if (empty($task)){
	error('You don`t have access to that task!');
	redirect(getUrl('project'));
}

$project = request('project','json',['ids'=>$task->project_id,'users'=>'true']);
if (empty($project)){
	error('You don`t have access to that project!');
	redirect(getUrl('project'));
}

if (post('name')){
	$user_permissions = post('users');
	debug(['POST'=>$_POST,'user_permissions'=>$user_permissions]);
	$users = [];
	if (!empty($user_permissions) && is_array($user_permissions)){
		foreach ($user_permissions as $uid => $perm){
			if (empty($project['users'][$uid])){
				error('User with id ? is not member of the project!',$uid);
				break;
			}
			if ($uid == $user->id) $perm = Task::PERMISSION_CREATOR;
			if ($perm == 0) continue;
			$u = $project['users'][$uid]['data'];
			$u['permission'] = $perm;
			$users[$uid] = $u;
		}
	}
	debug($users);
	if (!empty($users)){
		$new_task = new Task();
		$new_task->patch($_POST)->patch(['users'=>$users,'project_id'=>$task->project_id,'parent_task_id'=>$task_id]);
		try {
			if ($new_task->save()) redirect(getUrl('task',$task_id.'/view'));
		} catch (Exception $e){
			error($e);
		}
	} else error('Selection of at least one user is required!');
}

if (!$task->is_writable()) {
	error('You are not allowed to add sub-tasks to this task!');
	redirect('view');
}

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>
<form method="POST">
	<fieldset>
		<fieldset>
			<legend><?= t('Project')?></legend>
			<a href="<?= getUrl('project',$task->project_id.'/view')?>"><?= $project['name']?></a>
			&nbsp;&nbsp;&nbsp;&nbsp;
			<a href="<?= getUrl('files').'?path=project/'.$task->project_id ?>" class="symbol" title="show project files" target="_blank"></a>
		</fieldset>
		<legend><?= t('Add subtask to "?"','<a href="'.getUrl('task',$task->id.'/view').'">'.$task->name.'</a>') ?></legend>
		<fieldset><legend><?= t('Name')?></legend>
			<input type="text" name="name" value="<?= param('name') ?>" autofocus="true"/>
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
					<th title="<?= t('assignee')?>" class="symbol"></th>
					<th title="<?= t('read + write')?>" class="symbol"></th>
					<th title="<?= t('read only')?>" class="symbol"></th>
					<th title="<?= t('no access')?>" class="symbol"></th>
				</tr>
			<?php foreach ($task->project('users') as $id => $u) { $owner = $id == $user->id; ?>
				<tr>
					<td><?= $u['data']['login']?></td>
					<td><input type="radio" <?= $owner?'readonly="readonly"':''?>name="users[<?= $id ?>]" title="<?= t('assignee')?>" value="<?= Task::PERMISSION_ASSIGNEE ?>" /></td>
					<td><input type="radio" <?= $owner?'readonly="readonly"':''?>name="users[<?= $id ?>]" title="<?= t('read + write')?>" value="<?= Task::PERMISSION_READ_WRITE ?>" <?= $owner?'checked="checked" ':'' ?>/></td>
					<td><input type="radio" <?= $owner?'readonly="readonly"':''?>name="users[<?= $id ?>]" title="<?= t('read only')?>" value="<?= Task::PERMISSION_READ ?>" /></td>
					<td><input type="radio" <?= $owner?'readonly="readonly"':''?>name="users[<?= $id ?>]" title="<?= t('no access')?>" value="0" <?= $owner?'':'checked="checked" '?>/></td>
				</tr>
			<?php } ?>
			</table>
			<p>
			<?= t('Only selected users will be able to access the task!') ?>
			</p>
			<label>
				<input type="checkbox" name="notify" checked="true" />
				<?= t('notify users') ?>
			</label>
		</fieldset>
		<?php if (isset($services['bookmark'])){?>
		<fieldset><legend><?= t('Tags')?></legend>
			<input name="tags" type="text" value="<?= param('tags') ?>" />
		</fieldset>
		<?php }?>
		<fieldset>
			<legend><?= t('Start date')?></legend>
			<input name="start_date" type="date" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" value="<?= param('start_date',date('Y-m-d'));?>" />
		</fieldset>
	        <fieldset>
			<legend><?= t('Due date')?></legend>
			<input name="due_date" type="date" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" value="<?= $task->due_date ?>" />
		</fieldset>
		<button type="submit"><?= t('add subtask'); ?></button>
	</fieldset>
</form>

<?php include '../common_templates/closure.php'; ?>
