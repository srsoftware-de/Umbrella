<?php $title = 'Umbrella Model Management';

include '../bootstrap.php';
include 'controller.php';

require_login('model');

if ($project_id = param('project')){
	if ($name = param('name')){
		$model = new Model($project_id, $name,param('description'));
		$model->save();
		redirect(getUrl('model',$model->id.'/view'));
	}
} else {
	redirect(getUrl('model'));
}

include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>

<fieldset>
	<legend><?= t('Add Model'); ?></legend>
	<form method="POST">
	<fieldset>
		<legend><?= t('Name'); ?></legend>
		<input type="text" name="name" value="<?= param('name','') ?>" />
	</fieldset>
	<fieldset>
		<legend><?= t('Description'); ?></legend>
		<textarea name="description"><?= param('description','') ?></textarea>
	</fieldset>
	<button type="submit"><?= t('Save') ?></button>
	</form>
</fieldset>

<?php include '../common_templates/closure.php';
