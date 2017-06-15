<?php $title = 'Umbrella Task Management';

include '../bootstrap.php';
include 'controller.php';
require_login();

$parent_task_id = param('id');
if (!$parent_task_id) error('No parent task id passed!');

$task = load_task($parent_task_id);
$project_id = find_project($parent_task_id);
if (!$project_id) error('Was not able to determine project for this task!');

if ($name = post('name')){
	add_task($name,post('description'),$project_id,$parent_task_id);
    header('Location: ../index');
    die();
}

include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>
<form method="POST">
	<fieldset><legend>Add subtask to <?= $task['name']?></legend>
		<fieldset><legend>Name</legend>
		<input type="text" name="name" />
		</fieldset>
		<fieldset><legend>Description</legend>
		<textarea name="description"></textarea>
		</fieldset>
		<input type="submit" />
	</fieldset>
</form>

<?php include '../common_templates/closure.php'; ?>
