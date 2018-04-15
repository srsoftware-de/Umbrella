<?php $title = 'Umbrella Model Management';

include '../bootstrap.php';
include 'controller.php';

require_login('model');

$model_id = param('id1');
$terminal_id = param('id2');

if (!$model_id){
	error('No model id passed to terminal.');
	redirect(getUrl('model'));
}
if (!$terminal_id){
	error('No terminal id passed to terminal.');
	redirect(getUrl('model'));
}

$model = Model::load(['ids'=>$model_id]);
$terminal = $model->terminals($terminal_id);

if (param('name')){
	$terminal->patch($_POST);
	$terminal->save();
	redirect('../terminal/'.$terminal_id);
}

info('This Module is not functional, yet.');
include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>

<form method="post">
	<fieldset>
		<legend>
			<?= t('Edit terminal "?"',$terminal->name)?>
		</legend>
		<label>
			<?= t('Terminal name') ?>
			<input type="text" name="name" value="<?= $terminal->name ?>" />
		</label>
		<label>
			<?= t('Terminal description') ?>
			<textarea name="description"><?= $terminal->description ?></textarea>
		</label>
		<label>
			<?= t('Position') ?>
			<input type="number" min="0" step="1" name="x" value="<?= round($terminal->x) ?>" />
			<input type="number" min="0" step="1" name="y" value="<?= round($terminal->y)	 ?>" />
		</label>
		<button type="submit">
			<?= t('Save'); ?>
		</button>
	</fieldset>
</form>

<?php include '../common_templates/closure.php';