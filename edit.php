<?php include 'controller.php';

require_login('model');

if ($model_id = param('id')){
	$model = Model::load(['ids'=>$model_id]);
} else {
	error('No model id passed!');
	redirect(getUrl('model'));
}

if (param('name')){
	$model->patch($_POST);
	$model->save();
	redirect($model->url());
}

include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>

<fieldset>
	<legend><?= t('Edit Model'); ?></legend>
	<form method="POST">
	<fieldset>
		<legend><?= t('Name'); ?></legend>
		<input type="text" name="name" value="<?= param('name',$model->name) ?>" />
	</fieldset>
	<fieldset>
		<legend><?= t('Description'); ?></legend>
		<textarea name="description"><?= param('description',$model->description) ?></textarea>
	</fieldset>
	<button type="submit"><?= t('Save') ?></button>
	</form>
</fieldset>


<?php include '../common_templates/closure.php';