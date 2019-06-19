<?php include 'controller.php';

require_login('model');

$base_url = getUrl('model');

$phase_id = param('id');
if (empty($phase_id)){
	error('No phase id passed to edit!');
	redirect($base_url);
}

$phase = Phase::load(['ids'=>$phase_id]);
if (empty($phase)){
	error('You are not allowed to access this phase!');
	redirect($base_url);
}

$diagram = $phase->diagram();
if (empty($diagram)){
	error('You are not allowed to access this diagram!');
	redirect($base_url);
}

$name = param('name');
if (!empty($name)){
	$phase->patch(['name'=>$name,'description'=>param('description')])->save();
	redirect($base_url.'diagram/'.$diagram->id.'#phase'.$phase_id);
}

include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>

<form method="POST">
<fieldset>
	<legend><?= t('Edit phase "◊"',$phase->name)?></legend>
	<fieldset>
		<legend><?= t('Name')?></legend>
		<input type="text" name="name" value="<?= $phase->name ?>" autofocus="autofocus" />
	</fieldset>
	<fieldset>
		<legend><?= t('Description - <a target="_blank" href="◊">Markdown supported ↗cheat sheet</a>',t('MARKDOWN_HELP'))?></legend>
		<textarea name="description"><?= $phase->description ?></textarea>
	</fieldset>
	<button type="submit"><?= t('Save') ?></button>
</fieldset>
</form>
<?php include '../common_templates/closure.php'; ?>