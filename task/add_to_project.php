<?php $title = 'Umbrella Task Management';

include '../bootstrap.php';
include 'controller.php';
require_login();

$project_id = param('id');
if (!$project_id) error('No project id passed!');

if ($name = post('name')){
	add_task($name,post('description'),$project_id, null, post('start_date'), post('due_date'));
    redirect(getUrl('project', $project_id.'/view'));
}

include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>
<form method="POST">
	<fieldset><legend>Create new task</legend>
		<fieldset><legend>Name</legend>
			<input type="text" name="name" autofocus="true"/>
		</fieldset>
		<fieldset><legend>Description</legend>
		<textarea name="description"></textarea>
		</fieldset>
		<fieldset>
			<legend>Start date</legend>
			<input name="start_date" type="date" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" />
		</fieldset>
		<fieldset>
			<legend>Due date</legend>
			<input name="due_date" type="date" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" />
		</fieldset>
		<input type="submit" />
	</fieldset>
</form>

<?php include '../common_templates/closure.php'; ?>
