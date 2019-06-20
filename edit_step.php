<?php include 'controller.php';

require_login('model');

$base_url = getUrl('model');

$step_id = param('id');
if (empty($step_id)){
	error('No step id passed to edit!');
	redirect($base_url);
}

$step = Step::load(['ids'=>$step_id]);
if (empty($step)){
	error('You are not allowed to access this step!');
	redirect($base_url);
}

$phase = $step->phase();
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
	$step->patch($_POST)->save();
	redirect($base_url.'diagram/'.$diagram->id.'#step'.$step_id);
}

include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>

<form method="POST">
<fieldset>
	<legend><?= t('Edit step "◊"',$step->name)?></legend>
	<fieldset>
		<legend><?= t('Name')?></legend>
		<input type="text" name="name" value="<?= $step->name ?>" autofocus="autofocus" />
	</fieldset>
	<fieldset>
		<legend><?= t('Source')?></legend>
		<?php foreach ($phase->diagram()->parties() as $party_id => $party) { ?>
		<label>
			<input type="radio" name="source" value="<?= $party_id ?>" <?= $step->source==$party_id?'checked="checked" ':'' ?>/><?= $party->name ?>
		</label>
		<?php } ?>
	</fieldset>
	<fieldset>
		<legend><?= t('Destination')?></legend>
		<label>
			<input type="radio" name="destination" value="0" <?= $step->destination==0?'checked="checked" ':'' ?>/><?= t('None') ?>
		</label>
		<?php foreach ($phase->diagram()->parties() as $party_id => $party) { ?>
		<label>
			<input type="radio" name="destination" value="<?= $party_id ?>" <?= $step->destination==$party_id?'checked="checked" ':'' ?>/><?= $party->name ?>
		</label>
		<?php } ?>
	</fieldset>
	<fieldset>
		<legend><?= t('Description - <a target="_blank" href="◊">Markdown supported ↗cheat sheet</a>',t('MARKDOWN_HELP'))?></legend>
		<textarea name="description"><?= $step->description ?></textarea>
	</fieldset>
	<button type="submit"><?= t('Save') ?></button>
</fieldset>
</form>
<?php include '../common_templates/closure.php'; ?>
