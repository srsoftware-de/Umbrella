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

if ($flow_id = param('id2')){
	$flow = FlowInstance::load(['model_id'=>$model_id,'ids'=>$flow_id]);
} else {
	error('No flow id passed to model/'.$model->id.'/flow!');
	redirect($model->url());
}

if ($name = param('name')){
	$flow->base->patch($_POST);	
	$flow->base->save();
	redirect(getUrl('model',$model_id.'/view'));
}

include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>

<form method="post">
	<fieldset>
		<legend><?= t('Edit flow "?"',$flow->base->id); ?></legend>
		<fieldset>
			<legend><?= t('Name') ?></legend>
			<input type="text" name="name" value="<?= $flow->base->id ?>"/>
		</fieldset>
		<fieldset>
			<legend><?= t('Definition') ?></legend>
			<input type="text" name="definition" value="<?= htmlentities($flow->base->definition) ?>" />
		</fieldset>
		<fieldset>
			<legend><?= t('Description - <a target="_blank" href="?">Markdown supported ↗cheat sheet</a>',t('MARKDOWN_HELP'))?></legend>
			<textarea name="description"><?= $flow->base->description ?></textarea>			
		</fieldset>
		<button type="submit">
			<?= t('Save'); ?>
		</button>
	</fieldset>
</form>

<?php include '../common_templates/closure.php';