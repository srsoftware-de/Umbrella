<?php

include '../bootstrap.php';
include 'controller.php';

require_login();

$hash = param('file');

if (!$hash) error('No file hash passed to view!');

$allowed = false;

$file = load_file($hash);
if ($file === null){
	error('No such file!');
} else {
	load_users($file);
	foreach ($file['users'] as $id => $dummy){
		if ($id == $user->id) $allowed = true;
	}
}

if (!$allowed){
	error('You are not allowed to view this file!');
	include '../common_templates/head.php';
	include '../common_templates/main_menu.php';
	include 'menu.php';
	include '../common_templates/messages.php';
	include '../common_templates/closure.php';
	die();		
}

header('Content-Type: '.$file['type']);
header('Content-Disposition: attachment; filename="'.basename($file['path']).'"');
readfile($file['absolute']);