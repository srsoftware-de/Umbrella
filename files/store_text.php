<?php $title = 'Umbrella File Management';

include '../bootstrap.php';
include 'controller.php';

require_login('files');

if (($content = param('content')) && ($filename = param('filename'))){
	echo json_encode(store_text($filename,$content,$user->id));
	die();
}
if (!$content) error('No content submitted for file!');
if (!$filename) error('No name submitted for file!');

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; 
include '../common_templates/closure.php'; ?>
