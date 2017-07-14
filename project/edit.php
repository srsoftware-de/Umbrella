<?php $title = 'Umbrella Project Management';

include '../bootstrap.php';
include 'controller.php';

$project_id = param('id');
if (!$project_id) error('No project id passed to view!');


if ($name = post('name')){
	update_project($project_id,$name,post('description'));
	if ($redirect=param('redirect')){
		redirect($redirect);
	} else {
		redirect('view');
	}
}

$project = load_project($project_id);
include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>
<form method="POST">
	<fieldset>
		<legend>Edit Project</legend>
		<fieldset>
			<legend>Name</legend>
			<input type="text" name="name" value="<?= $project['name']; ?>"/>
		</fieldset>
		<fieldset>
			<legend>Description</legend>
			<textarea name="description"><?= $project['description']?></textarea>
		</fieldset>
		<input type="submit" />
	</fieldset>
</form>

<?php include '../common_templates/closure.php'; ?>
