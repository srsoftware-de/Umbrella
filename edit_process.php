<?php $title = 'Umbrella Model Management';

include '../bootstrap.php';
include 'controller.php';

require_login('model');

$model_id = param('id1');
$process_instance_id = param('id2');

if (!$model_id){
	error('No model id passed to terminal.');
	redirect(getUrl('model'));
}
if (!$process_instance_id){
	error('No process instance id passed to terminal.');
	redirect(getUrl('model'));
}

$model = Model::load(['ids'=>$model_id]);
$process = Process::load(['model_id'=>$model_id,'ids'=>$process_instance_id]);

if (param('name')){
	$process->base->patch($_POST);
	$process->base->save();
	redirect($model->url());
}

info('This Module is not functional, yet.');
include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>

<form method="post">
	<fieldset>
		<legend>
			<?= t('Edit process "?"',$process->base->id)?>
		</legend>
		<label>
			<?= t('Name') ?>
			<input type="text" name="name" value="<?= $process->base->id ?>" />
		</label>
		<label>
			<?= t('Description') ?>
			<textarea name="description"><?= $process->base->description ?></textarea>
		</label>
		<button type="submit">
			<?= t('Save'); ?>
		</button>
	</fieldset>
</form>

<?php include '../common_templates/closure.php';