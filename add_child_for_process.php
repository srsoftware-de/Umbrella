<?php $title = 'Umbrella Model Management';

include '../bootstrap.php';
include 'controller.php';

require_login('model');

if ($model_id = param('id1')){
	$model = Model::load(['ids'=>$model_id]);
} else {
	error('No Model id passed to add_process!');
	redirect(getUrl('model'));
}

$process_instance_id = param('id2');
if (!$process_instance_id){
	error('No terminal id passed to terminal.');
	redirect(getUrl('model'));
}

$model = Model::load(['ids'=>$model_id]);
$process_instance = ProcessInstance::load(['model_id'=>$model_id,'ids'=>$process_instance_id]);

if ($name = param('name')){
	if ($name == $process_instance->base->id){
		error('ProcessInstance may not be its own child at the moment!');
	} else {
		$project_id = $process_instance->base->project_id;
		$base = Process::load(['project_id' => $project_id,'ids'=>$name]);
		if ($base === null) {
			$base = new Process();
			$base->patch(['project_id'=>$project_id]);
			$base->patch($_POST);
			$base->save();
		}
		$child = new ProcessInstance();
		$child->base = $base;
		$child->patch(['model_id'=>$model_id,'process_id'=>$name,'parent'=>$process_instance->base->id,'x'=>0,'y'=>15]);
		$child->save();
		redirect($model->url());
	}
}

include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>

<form method="post">
	<fieldset>
		<legend>
			<?= t('Add child process to process "?"',$process_instance->base->id)?>
		</legend>
		<input type="hidden" name="parent" value="<?= $process_instance->base->id ?>" />
		<fieldset>
			<legend><?= t('Name') ?></legend>
			<input type="text" name="name" value="" />
		</fieldset>
		<fieldset>
			<legend><?= t('Description - <a target="_blank" href="?">Markdown supported ↗cheat sheet</a>',t('MARKDOWN_HELP'))?></legend>
			<textarea name="description"></textarea>
		</fieldset>
		<button type="submit">
			<?= t('Save'); ?>
		</button>
	</fieldset>
</form>

<?php include '../common_templates/closure.php';