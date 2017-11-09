<?php

include '../bootstrap.php';
include 'controller.php';

require_login('files');

$filename = param('file');

$absolute_path = get_absolute_path($filename);
if (!$absolute_path) {
	error('You are not allowed to access ?',$filename);
	include '../common_templates/head.php';
	include '../common_templates/main_menu.php';
	include 'menu.php';
	include '../common_templates/messages.php'; 
	include '../common_templates/closure.php';
	die();
}
header('Content-Disposition: attachment; filename="'.basename($absolute_path).'"');
readfile($absolute_path);
