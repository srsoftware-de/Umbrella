<?php

include '../bootstrap.php';
include 'controller.php';

require_login('files');

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
	if ($uid = post('file_user')){
		assign_user_to_file($uid,$hash);
		redirect('index');
	}
}

$user_list = request('user','list');
include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php';

if ($allowed){
?>

<form method="POST">
	<fieldset>
		<legend>Add user file (<?= $file['path'] ?>)</legend>
		<fieldset>
			<select name="file_user">
				<option value="" selected="true">= Select a user =</option>
				<?php foreach ($user_list as $id => $u){ 
				if (array_key_exists($id, $file['users'])) continue; ?>
				<option value="<?= $id ?>"><?= $u['login']?></option>
				<?php }?>
			</select>
		</fieldset>
		<input type="submit" />
	</fieldset>
</form>

<?php
}

include '../common_templates/closure.php'; ?>
