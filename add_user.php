<?php

include '../bootstrap.php';
include 'controller.php';

require_login('project');

if ($project_id = param('id')){
	$project = load_projects(['ids'=>$project_id,'single'=>true]);
	load_users($project);
	$title = $project['name'].' - Umbrella';

	$allowed = $project['users'][$user->id]['permissions'] == PROJECT_PERMISSION_OWNER;
	
	if ($allowed){
		$users = request('user','list');		
		if ($new_uid = post('project_user')){
			add_user_to_project($project,$users[$new_uid],post('permissions'));
			redirect('view');
		}
		
	} else error('You are not allowed to edit the user list of this project!');
} else error('No project id passed to view!');


include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; 

if ($allowed){ ?>
<form method="POST">
	<fieldset><legend><?= t('Add user to ?',$project['name'])?></legend>
		<fieldset>
			<select name="project_user">
				<option value="" selected="true"><?= t('== Select a user ==')?></option>
				<?php foreach ($users as $id => $u){ ?>
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
