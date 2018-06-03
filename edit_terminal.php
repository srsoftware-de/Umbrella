<?php $title = 'Umbrella Model Management';

include '../bootstrap.php';
include 'controller.php';

require_login('model');

$model_id = param('id1');
$terminal_instance_id = param('id2');

if (!$model_id){
	error('No model id passed to terminal.');
	redirect(getUrl('model'));
}
if (!$terminal_instance_id){
	error('No terminal id passed to terminal.');
	redirect(getUrl('model'));
}

$model = Model::load(['ids'=>$model_id]);
$terminal_instance = $model->terminal_instances($terminal_instance_id);

if ($name = param('name')){
	$base = $terminal_instance->base;
	if ($name == $base->id) unset($_POST['name']);
	$base->patch($_POST);
	$base->save();
	redirect($model->url());
}

include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>

<form method="post">
	<fieldset>
		<legend>
			<?= t('Edit terminal "?"',$terminal_instance->base->id)?>
		</legend>
		<fieldset>
			<legend><?= t('Name') ?></legend>
			<input type="text" name="name" value="<?= $terminal_instance->base->id ?>" />
		</fieldset>
		<fieldset>
			<legend><?= t('Description - <a target="_blank" href="?">Markdown supported ↗cheat sheet</a>',t('MARKDOWN_HELP'))?></legend>
			<textarea name="description"><?= $terminal_instance->base->description ?></textarea>
		</fieldset>
		<button type="submit">
			<?= t('Save'); ?>
		</button>
	</fieldset>
</form>

<?php include '../common_templates/closure.php';