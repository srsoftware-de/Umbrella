<?php $title = 'Umbrella Model Management';

include '../bootstrap.php';
include 'controller.php';

require_login('model');

if ($model_id = param('id1')){
	$model = Model::load(['ids'=>$model_id]);
} else {
	error('No model id passed!');
	redirect(getUrl('model'));
}

if ($path = param('id2')){
	$parts = explode(':',$path);
	$flow_id = array_pop($parts);
	$conn_id = array_pop($parts);
	$process_path = array_pop($parts);

	$flow = Flow::load(['connector'=>$conn_id,'ids'=>$flow_id,'process'=>$model_id.':'.$process_path]);
} else {
	error('No flow id passed to model/'.$model->id.'/flow!');
	redirect($model->url());
}

if ($name = param('name')){
	$flow->patch($_POST);
	$flow->save();
	redirect(getUrl('model',$model_id.'/view'));
}

info('This Module is not functional, yet.');
include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>

<form method="post">
	<fieldset>
		<legend>
			<?= t('Edit flow "?"',$flow->name); ?>
		</legend>
		<label>
			<?= t('Name') ?><input type="text" name="name" value="<?= $flow->name ?>"/>
		</label>
		<label>
			<?= t('Definition') ?><input type="text" name="definition" value="<?= $flow->definition ?>" />
		</label>
		<label>
			<?= t('Description') ?><textarea name="description"><?= $flow->description ?></textarea>
		</label>
		<button type="submit">
			<?= t('Save'); ?>
		</button>
	</fieldset>
</form>

<?php include '../common_templates/closure.php';