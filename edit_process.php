<?php $title = 'Umbrella Model Management';

include '../bootstrap.php';
include 'controller.php';

require_login('model');

$model_id = param('id1');
$process_id = param('id2');

if (!$model_id){
	error('No model id passed to terminal.');
	redirect(getUrl('model'));
}
if (!$process_id){
	error('No terminal id passed to terminal.');
	redirect(getUrl('model'));
}

$model = Model::load(['ids'=>$model_id]);
$process = $model->processes($process_id);

if (param('name')){
	$process->patch($_POST);
	$process->save();
	redirect('../process/'.$process_id);
}

info('This Module is not functional, yet.');
include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>

<form method="post">
	<fieldset>
		<legend>
			<?= t('Edit process "?"',$process->name)?>
		</legend>
		<label>
			<?= t('Process name') ?>
			<input type="text" name="name" value="<?= $process->name ?>" />
		</label>
		<label>
			<?= t('Process description') ?>
			<textarea name="description"><?= $process->description ?></textarea>
		</label>
		<button type="submit">
			<?= t('Save'); ?>
		</button>
	</fieldset>
</form>

<?php include '../common_templates/closure.php';