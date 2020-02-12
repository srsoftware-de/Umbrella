<?php include 'controller.php';

require_login('project');

if (!empty($_FILES['json_file']['size'])) $_POST['json'] = file_get_contents($_FILES['json_file']['tmp_name']);
if (!empty($_POST['json'])){
	$project = json_decode($_POST['json'],true);
	$tasks = isset($project['tasks']) ? $project['tasks']:[];
	$notes = isset($project['notes']) ? $project['notes']:[];
	unset($project['tasks']);
	unset($project['notes']);
	$response = request('project','add',$project,false,NO_CONVERSION);
	if (strpos($response, 'NEW_PROJECT_ID=')!==false){
		$new_project_id = substr($response, 15);

		foreach ($tasks as $task){
			$task['users'] = [$user->id => 1];
			request('task','add_to_project'.DS.$new_project_id,$task,1);
		}

	} else error('Tried to create new project, but dit not get an ID for the new project. There Seems to be a problem with the project/add script. Sorry, I cannot contiue. The response was: '.$response);
}

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>

<form method="post" enctype="multipart/form-data">
	<fieldset>
		<legend><?= t('Import project from exported json file')?></legend>
		<?= t('Select exported project file in JSON format:')?>
		<p><input type="file" name="json_file" id="fileupload"></p>
		<button type="submit"><?= t('Upload') ?></button>
	</fieldset>
</form>

<?php include '../common_templates/closure.php'; ?>