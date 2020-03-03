<?php include 'controller.php';

require_login('files');

$rel_file = param('file');

if (!$rel_file) error('No filename passed to delete!');
if (access_granted($rel_file)){
	$file = base_dir().DS.$rel_file;

	if ($file){
		if (param('confirm') == 'yes'){
			delete_file($file);
			redirect('index?path='.dirname($rel_file));
		}
	}
} else {
	error('You are not allowed to access "◊"',$rel_file);
}

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include '../common_templates/messages.php';

if ($file) echo dialog(t('Are you sure, you want to delete "◊"?',$rel_file),array(t('YES')=>'?file='.urlencode($rel_file).'&confirm=yes',t('NO')=>'index'));

include '../common_templates/closure.php'; ?>
