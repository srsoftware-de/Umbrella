<?php include 'controller.php';

// discover, if user is logged in
$user = empty($_SESSION['token']) ? null : getLocallyFromToken();
if ($user === null) validateToken('files');

$filename = param('file');

if (access_granted($filename)){
	header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment; filename="'.(basename($filename)).'"');
	die(readfile(base_dir().DS.$filename));
}
error('You are not allowed to access ◊',$filename);
include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php';
include '../common_templates/closure.php';
