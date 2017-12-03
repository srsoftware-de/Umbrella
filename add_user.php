<?php

include '../bootstrap.php';
include 'controller.php';

require_login('project');

$project_id = param('id');

if (!$project_id) error('No project id passed to view!');

$p = load_projects($project_id);
$title = $p['name'].' - Umbrella';
$current_users = load_users($project_id);
$allowed = false;
foreach ($current_users as $id => $u){
	if ($id == $user->id && ($u['permissions'] & PROJECT_PERMISSION_OWNER)) $allowed = true;
}

if ($allowed){
	if ($project_user = post('project_user')){
		add_user_to_project($project_id,$project_user,post('permissions'));
		redirect('view');
	}
	$user_list = request('user','list');
} else error('You are not allowed to edit the user list of this project!');

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; 

if ($allowed){ ?>
<form method="POST">
	<fieldset><legend><?= t('Add user to ?',$p['name'])?></legend>
		<fieldset>
			<select name="project_user">
				<option value="" selected="true"><?= t('== Select a user ==')?></option>
				<?php foreach ($user_list as $id => $u){ ?>
				<option value="<?= $id ?>"><?= $u['login']?></option>
				<?php }?>
			</select>
			<label>
			<input type="checkbox" name="permissions" value="<?= PROJECT_PERMISSION_PARTICIPANT ?>" checked="true" />
			<?= t('participant')?>
			</label>	
		</fieldset>
		<input type="submit" />
	</fieldset>
</form>
<?php }
include '../common_templates/closure.php'; ?>
