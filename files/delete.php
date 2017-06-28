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
	error('You are not allowed to add users to this file!');
} else {
	if (param('confirm') == 'yes'){
		delete_file($hash);
		redirect('index');
	}
}

$user_list = request('user','list');
include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php';

if ($allowed){
	echo dialog('Are you sure, you want to delete "'.$file['path'].'"?',array('YES'=>'?file='.$hash.'&confirm=yes','NO'=>'index'));
}

include '../common_templates/closure.php'; ?>
