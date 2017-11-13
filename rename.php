<?php $title = 'Umbrella Files';

include '../bootstrap.php';
include 'controller.php';

require_login('files');
$filename = param('file');

if ($newname = param('new_name')) renameFile($filename,$newname);

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php';

?>

<form method="POST">
	<fieldset>
		<legend><?= t('Enter new name for ?',basename($filename))?></legend>
		<input type="text" value="<?= basename($filename)?>" name="new_name" />
		<input type="submit" />
	</fieldset>
</form>

<?php include '../common_templates/closure.php'; ?>