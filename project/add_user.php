<?php

include '../bootstrap.php';
include 'controller.php';

require_login();

$user = current_user();
$project_id = param('id');

if (!$project_id) error('No project id passed to view!');

$p = load_project($project_id);
$title = $p['name'].' - Umbrella';

$ul = request('user','list');
if ($project_user = post('project_user')){
	debug($_POST);
	add_user_to_project($project_id,$project_user,post('permissions'));
	header('Location: user_list'); die();
}

include '../common_templates/head.php'; 
include '../common_templates/messages.php'; ?>

<h1>Add user to <?= $p['name']?></h1>
<form method="POST">
	<fieldset><legend>Add user to Project</legend>
		<fieldset>
			<select name="project_user">
				<option value="" selected="true">= Select a user =</option>
				<?php foreach ($ul as $u){ ?>
				<option value="<?= $u['id']?>"><?= $u['login']?></option>
				<?php }?>
			</select>
			<label>
			<input type="checkbox" name="permissions" value="2" checked="true">Participant
			</label>	
		</fieldset>
		<input type="submit" />
	</fieldset>
</form>

<?php include '../common_templates/closure.php'; ?>
