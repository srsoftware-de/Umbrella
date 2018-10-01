<?php $title = 'Umbrella Task Management';

include '../bootstrap.php';
include 'controller.php';
require_login('task');

$parent_task_id = param('id');
if (!$parent_task_id) error('No parent task id passed!');

$task = load_tasks(['ids'=>$parent_task_id]);
$project_id = find_project($parent_task_id);
if (!$project_id) error('Was not able to determine project for this task!');

$name = post('name');
$description = post('description');
$user_permissions = param('users');

$project = request('project','json',['ids'=>$project_id,'users'=>'true']);
$project_users = request('user','json',['ids'=>array_keys($project['users'])]);

if ($name){
	if (is_array($user_permissions) && !empty($user_permissions)){
		$users = [];
		foreach ($project_users as $uid => $u){
			$perm = $uid == $user->id ? TASK_PERMISSION_OWNER : $user_permissions[$uid];
			if ($perm == 0) continue;
			$u['permission'] = $perm;
			$users[$uid] = $u;
		}
		add_task($name,$description,$project_id,$parent_task_id, post('start_date'), post('due_date'),$users);
		redirect('../'.$parent_task_id.'/view');
	} else error('Selection of at least one user is required!');
}

include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>
<form method="POST">
	<fieldset>
		<fieldset>
			<legend><?= t('Project')?></legend>
			<a href="<?= getUrl('project',$project_id.'/view')?>" ><?= $project['name']?></a>
			&nbsp;&nbsp;&nbsp;&nbsp;
			<a href="<?= getUrl('files').'?path=project/'.$project_id ?>" class="symbol" title="show project files" target="_blank"></a>
		</fieldset>
		<legend><?= t('Add subtask to "?"','<a href="'.getUrl('task',$parent_task_id.'/view').'">'.$task['name'].'</a>') ?></legend>
		<fieldset><legend><?= t('Name')?></legend>
			<input type="text" name="name" value="<?= $name ?>" autofocus="true"/>
		</fieldset>
		<fieldset>
			<legend><?= t('Description - <a target="_blank" href="?">Markdown supported ↗cheat sheet</a>',t('MARKDOWN_HELP'))?></legend>
			<textarea name="description"><?= $description; ?></textarea>
		</fieldset>
		<fieldset>
			<legend><?= t('Estimated time')?></legend>
			<label>
				<?= t('? hours','<input type="number" name="est_time" />')?>
			</label>
		</fieldset>
		<fieldset>
			<legend><?= t('Users') ?></legend>
			<table>
				<tr>
					<th><?= t('User')?></th>
					<th title="<?= t('read + write')?>"><?= t('R/W')?></th>
					<th title="<?= t('read only')?>"><?= t('R')?></th>
					<th title="<?= t('no access')?>">–</th>
				</tr>
			<?php foreach ($project_users as $id => $u) { 
				$owner = $id == $user->id;
				?>
				<tr>
					<td><?= $u['login']?></td>
					<td><input type="radio" name="users[<?= $id ?>]" title="<?= t('read + write')?>" value="<?= TASK_PERMISSION_READ_WRITE ?>" <?= $owner?'checked="checked"':'' ?>/></td>
					<td><input type="radio" name="users[<?= $id ?>]" title="<?= t('read only')?>"    value="<?= TASK_PERMISSION_READ ?>" /></td>
					<td><input type="radio" name="users[<?= $id ?>]" title="<?= t('no access')?>"    value="0" <?= $owner?'':'checked="checked"' ?>/></td>
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
			<input name="start_date" type="date" value="<?= date('Y-m-d');?>" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" />
		</fieldset>
        <fieldset>
        	<legend><?= t('Due date')?></legend>
			<input name="due_date" type="date" value="<?= $task['due_date'] ?>" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" />
		</fieldset>
		<button type="submit"><?= t('add subtask'); ?></button>
	</fieldset>
</form>

<?php include '../common_templates/closure.php'; ?>
