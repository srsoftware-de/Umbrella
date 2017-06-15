<?php $title = 'Umbrella Task Management';

include '../bootstrap.php';
include 'controller.php';
require_login();

$task_id = param('id');
if (!$task_id) error('No task id passed!');

$task = load_task($task_id);
$project_id = $task['project_id'];
$task['project'] = request('project','json?id='.$project_id);

$parent_task_id = $task['parent_task_id'];
if ($parent_task_id) $task['parent'] = load_task($task['parent_task_id']);

// load other tasks of the project for the dropdown menu
$project_tasks = get_task_list('name',$project_id);

if ($name = post('name')){
	update_task($task_id,$name,post('description'),$project_id,$parent_task_id);
    header('Location: ../index');
    die();
}

include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>
<form method="POST">
	<fieldset><legend>Edit <?= $task['name']?></legend>
		<fieldset>
			<legend>Project</legend>
			<?= $task['project']['name']?>
		</fieldset>
		<?php if ($task['parent_task_id']){?>
		<fieldset>
			<legend>Parent task</legend>
			<select name="parent_task_id">
			<?php foreach ($project_tasks as $id => $project_task) {?>
				<option value="<?= $id ?>" <?= ($id == $task['parent_task_id'])?'selected="selected"':''?>><?= $project_task['name']?></option>
			<?php }?>
			</select>
		</fieldset>
		
		<?php }?>
		<fieldset>
			<legend>Task</legend>
			<input type="text" name="name" value="<?= $task['name'] ?>" />
		</fieldset>
		<fieldset><legend>Description</legend>
		<textarea name="description"><?= $task['description']?></textarea>
		</fieldset>
		<input type="submit" />
	</fieldset>
</form>

<?php include '../common_templates/closure.php'; ?>
