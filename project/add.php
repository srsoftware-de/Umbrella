<?php $title = 'Umbrella Project Management';

include '../bootstrap.php';
include 'controller.php';

require_login();
if ($name = post('name')){
	add_project($name,post('description'));
    header('Location: index');
    die();
}


include '../common_templates/head.php'; 
include '../common_templates/messages.php'; ?>
<form method="POST">
	<fieldset><legend>Create new Project</legend>
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
