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

$confirm = param('confirm');
if (!empty($confirm)){
	if ($confirm == 'yes') $phase->remove();
	redirect($base_url.'diagram/'.$diagram->id);
}

include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>

<fieldset>
	<legend><?= t('Confirm')?></legend>
	<?= t('You are about to delete the phase "◊". Are you sure you want to proceed?',$phase->name)?>
	<a class="button" href="?confirm=yes"><?= t('Yes')?></a>
	<a class="button" href="?confirm=no"><?= t('No')?></a>
</fieldset>

<?php include '../common_templates/closure.php'; ?>