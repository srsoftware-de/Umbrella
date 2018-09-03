<?php include 'controller.php';

require_login('files');

$rel_file = param('file');
if (in_array($rel_file,['company','project','user/'.$user->id])){
	error('You are not allowed to rename "?"!',$rel_file);
	redirect('index');
} elseif (access_granted($rel_file)){
	$newname = param('new_name');
	if ($newname && renameFile($rel_file,$newname)) redirect('index?path='.dirname($rel_file));
} else {
	error('You are not allowed to rename "?"!',$rel_file);
	$rel_file = null;
}

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php';

if ($rel_file){?>
<form method="POST">
	<fieldset>
		<legend><?= t('Enter new name for ?',basename($rel_file))?></legend>
		<input type="text" name="new_name" value="<?= htmlspecialchars(basename($rel_file))?>" />
		<input type="submit" />
	</fieldset>
</form>
<?php }

include '../common_templates/closure.php'; ?>