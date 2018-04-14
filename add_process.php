<?php $title = 'Umbrella Model Management';

include '../bootstrap.php';
include 'controller.php';

require_login('model');

if ($model_id = param('id')){
	$model = Model::load(['ids'=>$model_id]);
} else {
	error('No Model id passed to add_process!');
	redirect('index');
}

if ($name = param('name')){
	$process = new Process($name,$model_id,param('description'),param('parent_id'));
	$process->save();
	redirect('view');
}
include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<fieldset>
	<legend><?= t('Add process to "?"',$model->name); ?></legend>
	<form method="POST">
	<input type="hidden" name="model_id" value="<?= $model->id ?>" />
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

<?php include '../common_templates/bottom.php';
