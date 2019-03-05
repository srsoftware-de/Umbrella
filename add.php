<?php include 'controller.php';

require_login('model');

if ($project_id = param('project')){
	if ($name = param('name')){
		$model = new Process();
		$model->patch(['project_id'=>$project_id,'name'=>$name,'description'=>param('description'),'r'=>0]);
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
		<legend><?= t('Description - <a target="_blank" href="?">Markdown supported ↗cheat sheet</a>',t('MARKDOWN_HELP'))?></legend>
		<textarea name="description"><?= param('description','') ?></textarea>
	</fieldset>
	<button type="submit"><?= t('Save') ?></button>
	</form>
</fieldset>

<?php include '../common_templates/closure.php';
