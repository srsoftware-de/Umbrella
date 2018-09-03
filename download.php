<?php include 'controller.php';

require_login('files');

$filename = param('file');

if (access_granted($filename)){
	header('Content-Disposition: attachment; filename="'.basename($filename).'"');
	die(readfile(base_dir().DS.$filename));	
}
error('You are not allowed to access ?',$filename);
include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; 
include '../common_templates/closure.php';
