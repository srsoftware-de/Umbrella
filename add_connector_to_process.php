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
$process = $model->process_instances(array_shift($process_hierarchy));
while(!empty($process_hierarchy)) $process = $process->children(array_shift($process_hierarchy));

if ($name = param('name')){
	$base = ConnectorBase::load(['model_id'=>$model_id,'ids'=>$name]);
	if ($base === null){
		$base = new ConnectorBase();
		$base->patch($_POST);
		$base->save();
	}
	$connector = new Connector();
	$connector->base = $base;
	$connector->patch([
			'model_id'=>$model_id,
			'connector_id'=>$base->id,
			'process_instance_id'=>$process->id,
			'angle'=>180*param('direction')]);
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
			<?= t('Add connector to process "?"',$process->base->id)?>
		</legend>
		<input type="hidden" name="process_id" value="<?= $process->base->id ?>" />
		<input type="hidden" name="model_id" value="<?= $model->id ?>" />
		<p>
			<label>
				<input type="radio" name="direction" value="<?= Connector::DIR_IN ?>" checked="checked" onClick="$('input[type=text]').val('<?= $process->base->id ?>:in');" >
				<?= t('inbound connector') ?>
			</label>
			<label>
				<input type="radio" name="direction" value="<?= Connector::DIR_OUT ?>" onClick="$('input[type=text]').val('<?= $process->base->id ?>:out');" >
				<?= t('outbound connector') ?>
			</label>
		</p>
		<label>
			<?= t('Connector name') ?>
			<input type="text" name="name" value="" autofocus="true" />
		</label>
		<button type="submit">
			<?= t('Save'); ?>
		</button>
	</fieldset>
</form>

<?php include '../common_templates/closure.php';