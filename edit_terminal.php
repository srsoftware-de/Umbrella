<?php include 'controller.php';

require_login('model');

$terminal_id = param('id');
if (empty($terminal_id)){
	error('No terminal id specified!');
	redirect(getUrl('model'));
}

$terminal = Terminal::load(['ids'=>$terminal_id]);
$project = $terminal->project();
if (empty($project)){
	error('You are not allowed to access that terminal!');
	redirect(getUrl('model'));
}

if (param('name')){
	$terminal->patch($_POST)->save();
	redirect(getUrl('model','terminal/'.$terminal_id));
}

include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>

<form method="post">
	<fieldset>
		<legend>
			<?= t('Edit terminal "?"',$terminal->name)?>
		</legend>
		<fieldset>
			<legend><?= t('Name') ?></legend>
			<input type="text" name="name" value="<?= $terminal->name ?>" />
		</fieldset>
		<fieldset>
			<legend><?= t('Description - <a target="_blank" href="?">Markdown supported ↗cheat sheet</a>',t('MARKDOWN_HELP'))?></legend>
			<textarea name="description"><?= $terminal->description ?></textarea>
		</fieldset>
		<button type="submit">
			<?= t('Save'); ?>
		</button>
	</fieldset>
</form>

<?php include '../common_templates/closure.php';