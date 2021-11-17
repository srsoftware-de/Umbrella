<?php include 'controller.php';
require_login('task');

$project_id = param('id');
if (empty($project_id)){
	error('No project id passed!');
	redirect(getUrl('project'));
}

$project = request('project','json',['id'=>$project_id,'users'=>'true'],false,OBJECT_CONVERSION);
if (empty($project)){
	error('You don`t have access to that project!');
	redirect(getUrl('project'));
}

if ($name = post('name')){
	$user_permissions = param('users');
	$users = [];
	if (!empty($user_permissions) && is_array($user_permissions)){
		foreach ($user_permissions as $uid => $perm){
			if (empty($project->users->{$uid})){
				error('User with id ◊ is not member of the project!',$uid);
				break;
			}
			if ($uid == $user->id) $perm = Task::PERMISSION_CREATOR;
			if ($perm == 0) continue;
			$u = $project->users->{$uid}->data;
			$u->permission = $perm;
			$users[$uid] = $u;
		}
	}
	if (!empty($users)){
		$task = new Task();
		try {
			if ($task->patch($_POST)->patch(['project_id'=>$project_id,'users'=>$users])->save()) redirect(getUrl('task',$task->id.'/view'));
		} catch (Exception $e){
			error($e);
		}
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
			<a href="<?= getUrl('project',$project_id.'/view')?>"><?= $project->name ?></a>
			&nbsp;&nbsp;&nbsp;&nbsp;
			<a href="<?= getUrl('files').'?path=project/'.$project_id ?>" class="symbol" title="show project files" target="_blank"></a>
		</fieldset>
		<fieldset><legend><?= t('Name')?></legend>
			<input type="text" name="name" value="<?= $name ?>" autofocus="true"/>
		</fieldset>
		<fieldset>
			<legend><?= t('Description – <a target="_blank" href="◊">↗Markdown</a> and <a target="_blank" href="◊">↗PlantUML</a> supported',[t('MARKDOWN_HELP'),t('PLANTUML_HELP')]) ?></legend>
			<textarea id="preview-source" name="description"><?= param('description'); ?></textarea>
			<div id="preview" />
		</fieldset>
		<fieldset>
			<legend><?= t('Estimated time')?></legend>
			<label>
				<?= t('◊ hours','<input type="number" name="est_time" value="'.param('est_time').'" />')?>
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
			<?php foreach ($project->users as $ud) {
				$u = $ud->data;
				$owner = $u->id == $user->id;
				?>
				<tr>
					<td><?= $u->login?></td>
					<td><input type="radio" name="users[<?= $u->id ?>]" title="<?= t('assignee')?>" value="<?= Task::PERMISSION_ASSIGNEE ?>" <?= $owner?'checked="checked" ':'' ?>/></td>
					<td><input type="radio" name="users[<?= $u->id ?>]" title="<?= t('read + write')?>" value="<?= Task::PERMISSION_READ_WRITE ?>" /></td>
					<td><input type="radio" name="users[<?= $u->id ?>]" title="<?= t('read only')?>" value="<?= Task::PERMISSION_READ ?>" /></td>
					<td><input type="radio" name="users[<?= $u->id ?>]" title="<?= t('no access')?>" value="0" <?= $owner?'':'checked="checked" '?>/></td>
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
		<?php if (isset($services['bookmark'])){ 
		    $bkmk = request('bookmark','json',['url'=>getUrl('project',$project_id.'/view')],false,OBJECT_CONVERSION); ?>
		<fieldset><legend><?= t('Tags')?></legend>
			<input name="tags" type="text" value="<?= param('tags',implode(' ',$bkmk->tags)) ?>" />
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
