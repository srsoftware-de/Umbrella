<?php include 'controller.php';

require_login('model');

$process_id = param('id');
if (empty($process_id)) {
	error('No model id passed to view!');
	redirect(getUrl('model'));
}

$process = Process::load(['ids'=>$process_id]);

if ($name = param('name')){
	$terminal = Terminal::load(['project_id'=>$process->project_id,'name'=>$name]);
	if (empty($terminal)) {
		$terminal = new Terminal();
		$terminal->patch(['project_id'=>$process->project_id,'name'=>$name,'description'=>param('description'),'type'=>param('type')])->save();
	}
	$process->add($terminal);
	redirect('view');
}
include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>

<fieldset>
	<legend><?= t('Add Terminal Instance to "?"',$process->name); ?></legend>
	<form method="POST">
	<fieldset>
	<legend><?= t('Type')?></legend>
	<label>
		<input type="radio" name="type" checked="checked" value="<?= Terminal::TERMINAL ?>"><?= t('Terminal')?>
	</label>
	<label>
		<input type="radio" name="type" value="<?= Terminal::DATABASE ?>"><?= t('Database')?>
	</label>
	</fieldset>
	<fieldset>
		<legend><?= t('Name'); ?></legend>
		<input type="text" name="name" value="<?= param('name','') ?>" />
	</fieldset>

	<fieldset>
		<legend><?= t('Description - <a target="_blank" href="?">Markdown supported ↗cheat sheet</a>',t('MARKDOWN_HELP'))?></legend>
		<textarea name="description"><?= param('description','') ?></textarea>
	</fieldset>
	<button type="submit"><?= t('Save') ?></button>
	</form>
</fieldset>

<?php include '../common_templates/closure.php';
