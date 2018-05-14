<?php $title = 'Umbrella Model Management';

include '../bootstrap.php';
include 'controller.php';

require_login('model');

if ($model_id = param('id')){
	$model = Model::load(['ids'=>$model_id]);
} else {
	error('No Model id passed to add_terminal!');
	redirect('index');
}

if ($name = param('name')){
	$base = TerminalBase::load(['project_id'=>$model->project_id,'ids'=>$name]);
	if ($base === null) {
		$base = new TerminalBase();		
		$base->patch($_POST);		
		$base->save();
	}
	$terminal = new Terminal();
	$terminal->base = $base;
	$terminal->patch(['model_id'=>$model_id,'terminal_id'=>$name,'x'=>0,'y'=>0]);
	$terminal->save();
	redirect('view');
}
include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>

<fieldset>
	<legend><?= t('Add Terminal to "?"',$model->name); ?></legend>
	<form method="POST">
	<input type="hidden" name="project_id" value="<?= $model->project_id ?>" />
	<input type="hidden" name="model_id" value="<?= $model->id ?>" />
	<fieldset>
	<legend><?= t('Type')?></legend>
	<label>
		<input type="radio" name="type" checked="checked" value="<?= TerminalBase::TERMINAL ?>"><?= t('Terminal')?>
	</label>
	<label>
		<input type="radio" name="type" value="<?= TerminalBase::DATABASE ?>"><?= t('Database')?>
	</label>
	</fieldset>
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
