<?php include 'controller.php';

require_login('model');

$base_url = getUrl('model');

$diagram_id = param('id');
if (empty($diagram_id)){
	error('No diagram id passed to edit!');
	redirect($base_url);
}

$diagram = Diagram::load(['ids'=>$diagram_id]);
if (empty($diagram)){
	error('You are not allowed to access this diagram!');
	redirect($base_url);
}

$name = param('name');
if (!empty($name)){
	$diagram->patch(['name'=>$name,'description'=>param('description')])->save();
	redirect($base_url.'diagram/'.$diagram->id);
}

include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>

<form method="POST">
<fieldset>
	<legend><?= t('Edit diagram "◊"',$diagram->name)?></legend>
	<fieldset>
		<legend><?= t('Name')?></legend>
		<input type="text" name="name" value="<?= $diagram->name ?>" autofocus="autofocus" />
	</fieldset>
	<fieldset>
		<legend><?= t('Description - <a target="_blank" href="◊">Markdown supported ↗cheat sheet</a>',t('MARKDOWN_HELP'))?></legend>
		<textarea name="description"><?= $diagram->description ?></textarea>
	</fieldset>
	<button type="submit"><?= t('Save') ?></button>
</fieldset>
</form>
<?php include '../common_templates/closure.php'; ?>