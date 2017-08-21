<?php

include '../bootstrap.php';
include 'controller.php';

require_login();

$task_id = param('id');

if (!$task_id) error('No task id passed to view!');

$task = load_tasks($task_id);

$project_users_ids = request('project','user_list',['id'=>$task['project_id']]);
$project_users = request('user','list',['ids'=>implode(',', array_keys($project_users_ids))]);

load_users($task,$project_users);

$title = $task['name'].' - Umbrella';

$allowed = false;
foreach ($task['users'] as $id => $u){
	if ($id == $user->id){
		if ($u['permissions'] & TASK_PERMISSION_OWNER) {
			$allowed = true;
		} elseif ($u['permissions'] & TASK_PERMISSION_PARTICIPANT) $allowed = true;
	}
}

if ($allowed){
	if ($task_user = post('task_user')){
		add_user_to_task($task_id,$task_user,post('permissions'));
		redirect('view');
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
				<?php foreach ($project_users as $id => $u){ ?>
				<option value="<?= $id ?>"><?= $u['login']?></option>
				<?php }?>
			</select>
			<label>
			<input type="checkbox" name="permissions" value="<?= TASK_PERMISSION_PARTICIPANT ?>" checked="true">Participant
			</label>	
		</fieldset>
		<input type="submit" />
	</fieldset>
</form>
<?php }
include '../common_templates/closure.php'; ?>
