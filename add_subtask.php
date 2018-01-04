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
$user_ids = param('users');

$project_users_ids = request('project','user_list',['id'=>$project_id]);
$project_users = request('user','list',['ids'=>implode(',', array_keys($project_users_ids))]);

if ($name){
	if (is_array($user_ids) && !empty($user_ids)){
		$users = array_intersect_key($project_users, array_flip($user_ids));		
		add_task($name,$description,$project_id,$parent_task_id, post('start_date'), post('due_date'),$users);
		redirect('../'.$parent_task_id.'/view');
	} else error('Selection of at least one user is required!');
}

include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>
<form method="POST">
	<fieldset>
		<legend><?= t('Add subtask to "?"','<a href="'.getUrl('task',$parent_task_id.'/view').'">'.$task['name'].'</a>') ?></legend>
		<fieldset><legend><?= t('Name')?></legend>
			<input type="text" name="name" value="<?= $name ?>" autofocus="true"/>
		</fieldset>
		
		<fieldset>
			<legend><?= t('Description - <a target="_blank" href="?">click here for Markdown and extended Markdown cheat sheet</a>',t('MARKDOWN_HELP'))?></legend>
			<textarea name="description"><?= $description; ?></textarea>
		</fieldset>
		<fieldset>
			<legend><?= t('Users') ?></legend>
			<select name="users[]" multiple="true">
			<?php foreach ($project_users as $id => $u){ ?>
				<option value="<?= $id ?>" <?= ($id == $user->id)?'selected="true"':''?>><?= $u['login'] ?></option>
			<?php } ?>
			</select>
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
		<input type="submit" />		
	</fieldset>
</form>

<?php include '../common_templates/closure.php'; ?>
