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
$process_hierarchy = explode('.',$process_id);
$process = $model->processes(array_shift($process_hierarchy));
while(!empty($process_hierarchy)) $process = $process->children(array_shift($process_hierarchy));

if ($name = param('name')){
	$connector = new Connector();
	$connector->patch($_POST);
	$connector->patch(['process_id'=>$process->id,'angle'=>180*param('direction')]);	
	$connector->save();
	redirect($model->url());
}


info('This Module is not functional, yet.');
include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>

<form method="post">
	<fieldset>
		<legend>
			<?= t('Add connector to process "?"',$process->name)?>
		</legend>
		<input type="hidden" name="process_id" value="<?= $process_id ?>" />
		<label>
			<?= t('Connector name') ?>
			<input type="text" name="name" value="" />
		</label>
		<label>
			<?= t('Connector description') ?>
			<textarea name="description"></textarea>
		</label>
		<p>
			<label>
				<input type="radio" name="direction" value="<?= Connector::DIR_IN ?>" checked="checked">
				<?= t('inbound connector') ?>
			</label>
			<label>
				<input type="radio" name="direction" value="<?= Connector::DIR_OUT ?>">
				<?= t('outbound connector') ?>
			</label>
		</p>
		<button type="submit">
			<?= t('Save'); ?>
		</button>
	</fieldset>
</form>

<?php include '../common_templates/closure.php';