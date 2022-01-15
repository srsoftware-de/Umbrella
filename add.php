<?php include 'controller.php';

require_login('notes');

if (($uri = param('uri')) && ($note = param('note'))){
	$note = new Note($uri, $note);
	$note->save()->notify();
	redirect($url(note->uri));
}

include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<fieldset>
	<legend><?= t('Add note'); ?></legend>
	<form method="POST">
	<fieldset>
		<legend><?= t('URL'); ?></legend>
		<input type="text" name="url" value="<?= param('url','') ?>" />
	</fieldset>
	<fieldset>
		<legend><?= t('note') ?></legend>
		<textarea id="preview-source" name="note"><?= param('note','') ?></textarea>
		<div id="preview"></div>
	</fieldset>
	<button type="submit"><?= t('Save') ?></button>
	</form>
</fieldset>

<?php include '../common_templates/bottom.php';
