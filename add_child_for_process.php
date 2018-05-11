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

$process_id = param('id2');
if (!$process_id){
	error('No terminal id passed to terminal.');
	redirect(getUrl('model'));
}

$process_hierarchy = explode('.',$process_id);
$process = $model->process_instances(array_shift($process_hierarchy));
while(!empty($process_hierarchy)) $process = $process->children(array_shift($process_hierarchy));

if ($name = param('name')){
	if ($name == $process->base->id){
		error('Process may not be its own child at the moment!');
	} else {
		$base = ProcessBase::load(['model_id'=>$model_id,'ids'=>$name]);
		if ($base === null) {
			$base = new ProcessBase();
			$base->patch(['model_id'=>$model_id]);
			$base->patch($_POST);
			$base->save();
		}
		$child = new Process();
		$child->base = $base;
		$child->patch(['model_id'=>$model_id,'process_id'=>$name,'parent'=>$process->base->id,'x'=>50,'y'=>50]);
		$child->save();
		redirect($model->url());
	}
}

info('This Module is not functional, yet.');
include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>

<form method="post">
	<fieldset>
		<legend>
			<?= t('Add child process to process "?"',$process->base->id)?>
		</legend>
		<input type="hidden" name="parent" value="<?= $process->base->id ?>" />
		<label>
			<?= t('Name') ?>
			<input type="text" name="name" value="" />
		</label>
		<label>
			<?= t('Description') ?>
			<textarea name="description"></textarea>
		</label>
		<button type="submit">
			<?= t('Save'); ?>
		</button>
	</fieldset>
</form>

<?php include '../common_templates/closure.php';