<?php include 'controller.php';

require_login('model');

$process_id = param('id');
if (empty($process_id)) {
	error('No model id passed to view!');
	redirect(getUrl('model'));
}

$process = Process::load(['ids'=>$process_id]);

if ($name = param('name')){
	$child = Process::load(['project_id'=>$process->project_id,'name'=>$name]);
	if (empty($child)){
		$child = new Process();
		$child->patch(['project_id'=>$process->project_id,'name'=>$name,'description'=>param('description')])->save();
	}
	$process->add($child);
	redirect(getUrl('model','process/'.$process->id));
}
include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>

<fieldset>
	<legend><?= t('Add process to ◊',t(($process->r == 0?'model':'process').' "?"',$process->name)); ?></legend>
	<form method="POST">
	<input type="hidden" name="project_id" value="<?= $process->project_id ?>" />
	<input type="hidden" name="model_id" value="<?= $process->id ?>" />
	<fieldset>
		<legend><?= t('Name'); ?></legend>
		<input type="text" name="name" value="<?= param('name','') ?>" autofocus="autofocus" />
	</fieldset>
	<fieldset>
		<legend><?= t('Description - <a target="_blank" href="◊">Markdown supported ↗cheat sheet</a>',t('MARKDOWN_HELP'))?></legend>
		<textarea name="description"><?= param('description','') ?></textarea>
	</fieldset>
	<button type="submit"><?= t('Save') ?></button>
	</form>
</fieldset>

<?php include '../common_templates/closure.php';
