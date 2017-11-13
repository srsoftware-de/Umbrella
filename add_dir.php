<?php $title = 'Umbrella File Management';

include '../bootstrap.php';
include 'controller.php';

require_login('files');

$newdir = param('dirname');
if ($newdir) {
	$dir = create_dir($newdir);
	if ($dir) redirect('index?path='.$dir);
}
include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>
<form method="POST" enctype="multipart/form-data">
	<fieldset>
		<legend><?= t('Create new directory')?></legend>
		<fieldset>
			<legend>Name</legend>		
			<input type="text" name="dirname" />
		</fieldset>
		<input type="submit" />
	</fieldset>
</form>

<?php include '../common_templates/closure.php'; ?>
