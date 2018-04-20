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
	error('No process id passed to terminal.');
	redirect(getUrl('model'));
}

$model = Model::load(['ids'=>$model_id]);
$process_hierarchy = explode('.',$process_id);
$process = $model->processes(array_shift($process_hierarchy));
while(!empty($process_hierarchy)) $process = $process->children(array_shift($process_hierarchy));

if (param('name')){
	$process->patch($_POST);
	$process->save();
	redirect($model->url());
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
		<label>
			<?= t('Position') ?>
			<input type="number" step="1" name="x" value="<?= round($process->x) ?>" />
			<input type="number" step="1" name="y" value="<?= round($process->y)	 ?>" />
		</label>
		<button type="submit">
			<?= t('Save'); ?>
		</button>
	</fieldset>
</form>

<?php include '../common_templates/closure.php';