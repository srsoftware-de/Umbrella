<?php

include '../bootstrap.php';
include 'controller.php';

require_login('files');

$filename = param('file');

if (!$filename) error('No filename passed to delete!');

$file = get_absolute_path($filename);

if ($file){
	if (param('confirm') == 'yes'){
		delete_file($file);
		redirect('index?path='.dirname($filename));
	}
}

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php';

if ($file){
	echo dialog(t('Are you sure, you want to delete "?"?',$filename),array('YES'=>'?file='.urlencode($filename).'&confirm=yes','NO'=>'index'));
}

include '../common_templates/closure.php'; ?>
