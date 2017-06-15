<?php $title = 'Umbrella Task Management';

include '../bootstrap.php';
include 'controller.php';
require_login();

$project_id = param('id');
if (!$project_id) error('No project id passed!');

if ($name = post('name')){
	add_task($name,post('description'),$project_id);
    redirect(getUrl('project', $project_id.'/view'));
}

include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>
<form method="POST">
	<fieldset><legend>Create new task</legend>
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
