<?php

include '../bootstrap.php';
include 'controller.php';

require_login('project');

if ($project_id = param('id')){
	$project = Project::load(['ids'=>$project_id]);
	$title = $project->name.' - Umbrella';

	// only project owner has allowance to add new users
	$allowed = $project->users[$user->id]['permission'] == PROJECT_PERMISSION_OWNER;
	
	if (!$allowed){
		error('You are not allowed to edit the user list of this project!');
		redirect(getUrl('project',$project_id.'/view'));
	}
	
	$users = request('user','json');
	if ($new_uid = post('new_user_id')){
		$project->addUser($users[$new_uid]);
		redirect('view');
	}
} else {
	error('No project id passed to view!');
	redirect(getUrl('project'));
}


include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; 

if ($allowed){ ?>
<form method="POST">
	<fieldset><legend><?= t('Add user to ?',$project->name)?></legend>
		<fieldset>
			<select name="new_user_id">
				<option value="" selected="true"><?= t('== Select a user ==')?></option>
				<?php foreach ($users as $id => $u){ ?>
				<option value="<?= $id ?>"><?= $u['login']?></option>
				<?php }?>
			</select>
			<label>
			<input type="checkbox" name="notify" value="on" checked="true" />
			<?= t('notify user')?>
			</label>	
		</fieldset>
		<input type="submit" />
	</fieldset>
</form>
<?php }
include '../common_templates/closure.php'; ?>
