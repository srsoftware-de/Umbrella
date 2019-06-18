<?php include 'controller.php';

require_login('model');

$phase_id = param('id');
if (empty($phase_id)) {
	error('No phase id passed!');
	redirect(getUrl('model'));
}

$phase = Phase::load(['ids'=>$phase_id]);
if (empty($phase)){
	error('You are not allowed to access that phase!');
	redirect(getUrl('model'));
}

if (empty($phase->diagram())){
	error('You are not allowed to access that diagram!');
	redirect(getUrl('model'));
}

if ($name = param('name')){
	$step = new Step();
	try {
		$step->patch(['phase_id'=>$phase_id,'name'=>$name,'description'=>param('description'),'source'=>param('source'),'destination'=>param('destination')])->save();
		redirect(getUrl('model','diagram/'.$phase->diagram()->id));
	} catch (Exception $e){
		error($e);
	}
}

include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>

<fieldset>
<legend><?= t('Add step to ◊',$phase->name); ?></legend>
	<form method="POST">
	<fieldset>
		<legend><?= t('Name'); ?></legend>
		<input type="text" name="name" value="<?= param('name','') ?>" />
	</fieldset>
	<fieldset>
		<legend><?= t('Source')?></legend>
		<label>
			<input type="radio" name="source" value="0" /><?= t('None') ?>
		</label>
		<?php foreach ($phase->diagram()->parties() as $party_id => $party) { ?>
		<label>
			<input type="radio" name="source" value="<?= $party_id ?>" /><?= $party->name ?>
		</label>
		<?php } ?>
	</fieldset>
	<fieldset>
		<legend><?= t('Destination')?></legend>
		<label>
			<input type="radio" name="destination" value="0" /><?= t('None') ?>
		</label>
		<?php foreach ($phase->diagram()->parties() as $party_id => $party) { ?>
		<label>
			<input type="radio" name="destination" value="<?= $party_id ?>" /><?= $party->name ?>
		</label>
		<?php } ?>
	</fieldset>
	<fieldset>
		<legend><?= t('Description - <a target="_blank" href="◊">Markdown supported ↗cheat sheet</a>',t('MARKDOWN_HELP'))?></legend>
		<textarea name="description"><?= param('description','') ?></textarea>
	</fieldset>
	<button type="submit"><?= t('Save') ?></button>
	</form>
</fieldset>

<?php include '../common_templates/closure.php'; ?>