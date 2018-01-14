<?php $title = 'Umbrella File Management';

include '../bootstrap.php';
include 'controller.php';

require_login('files');

$dir = param('dir');
if (access_granted($dir) && !in_array($dir,['company','project'])){
	if ($newdir = param('dirname')) {
		$dir = create_dir($dir.DS.$newdir);
		if ($dir) redirect('index?path='.$dir);
	}
} else {
	error('You are not allowed to add folders to "?"!',$dir);
	redirect(getUrl('files','?path='.$dir));
}
include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>
<form method="POST" enctype="multipart/form-data">
	<fieldset>
		<legend><?= t('Create new directory in "?"',$dir)?></legend>
		<fieldset>
			<legend>Name</legend>		
			<input type="text" name="dirname" autofocus="true" />
		</fieldset>
		<input type="submit" />
	</fieldset>
</form>

<?php include '../common_templates/closure.php'; ?>
