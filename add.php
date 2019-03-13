<?php include 'controller.php';

require_login('poll');

if (param('name')){
	$poll = new Poll();
	$poll->patch($_POST)->save();
	redirect(getUrl('poll','options?id='.$poll->id));
}

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<fieldset>
	<legend><?= t('Add new poll')?></legend>
	<form method="POST">
		<label>
			<?= t('Poll title')?>
			<input type="text" name="name" value="<?= param('name')?>" />
		</label>
		<label>
			<?= t('Poll description')?>
			<textarea name="description"><?= trim(param('description',''))?></textarea>
		</label>
		<button type="submit"><?= t('Create poll') ?></button>
	</form>
</fieldset>

<?php include '../common_templates/closure.php';