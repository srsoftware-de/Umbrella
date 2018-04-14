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

if ($name = param('name')){
	$connection = new Connection($process_id,$name,param('description'),param('direction')=='in');
	$connection->save();
	debug($connection,1);
}

$model = Model::load(['ids'=>$model_id]);
$process = $model->processes($process_id);

info('This Module is not functional, yet.');
include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>

<form method="post">
	<fieldset>
		<legend>
			<?= t('Add connection to process "?"',$process->name)?>
		</legend>
		<label>
			<?= t('Connection name') ?>
			<input type="text" name="name" value="" />
		</label>
		<label>
			<?= t('Connection description') ?>
			<textarea name="description"></textarea>
		</label>
		<p>
			<label>
				<input type="radio" name="direction" value="in" checked="checked">
				<?= t('inbound connection') ?>
			</label>
			<label>
				<input type="radio" name="direction" value="out">
				<?= t('outbound connection') ?>
			</label>
		</p>
		<button type="submit">
			<?= t('Save'); ?>
		</button>
	</fieldset>
</form>

<?php include '../common_templates/closure.php';