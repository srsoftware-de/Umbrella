<?php

include '../bootstrap.php';
include 'controller.php';

require_login();

$task_id = param('id');

if (!$task_id) error('No task id passed to view!');

$task = load_task($task_id);
$title = $task['name'].' - Umbrella';
$project_users_ids = request('project','user_list?id='.$task['project_id']);
$project_users = request('user','list?ids='.implode(',', array_keys($project_users_ids)),true);
debug($project_users,true);
$allowed = false;
foreach ($ul as $u){
	if ($u['user_id'] == $user->id && ($u['permissions'] & task_PERMISSION_OWNER)) $allowed = true;
}

if ($allowed){
	$ul = request('user','list');
	if ($task_user = post('task_user')){
		add_user_to_task($task_id,$task_user,post('permissions'));
		header('Location: user_list'); die();
	}
} else error('You are not allowed to edit the user list of this task!');

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; 

if ($allowed){ ?>
<h1>Add user to <?= $task['name']?></h1>
<form method="POST">
	<fieldset><legend>Add user to task</legend>
		<fieldset>
			<select name="task_user">
				<option value="" selected="true">= Select a user =</option>
				<?php foreach ($ul as $u){ ?>
				<option value="<?= $u['id']?>"><?= $u['login']?></option>
				<?php }?>
			</select>
			<label>
			<input type="checkbox" name="permissions" value="<?= task_PERMISSION_PARTICIPANT ?>" checked="true">Participant
			</label>	
		</fieldset>
		<input type="submit" />
	</fieldset>
</form>
<?php }
include '../common_templates/closure.php'; ?>
