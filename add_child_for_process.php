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
	$child = new Process();
	$child->patch($_POST);
	$child->save();
	$process->addChild($child->id);
	redirect($model->url());
}

info('This Module is not functional, yet.');
include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>

<form method="post">
	<fieldset>
		<legend>
			<?= t('Add child process to process "?"',$process->name)?>
		</legend>
		<input type="hidden" name="parent_process" value="<?= $process_id ?>" />
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