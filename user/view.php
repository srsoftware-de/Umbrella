<?php

include '../bootstrap.php';
include 'controller.php';

require_user_login();
$user_id = param('id');

if ($user->id != 1 && $user_id != $user->id){
	error('Currently, only admin can view other users!');
	redirect('../index');
}


$u = load_user($user_id);
$title = $u->login.' - Umbrella';

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php';
?>
<h1><?= $u->login ?></h1>
<table class="vertical">
	<tr>
		<th><?= t('Username');?></th><td><?= $u->login;?></td>
	</tr>
	<tr>
		<th><?= t('Password (hashed)') ?></th><td><?= $u->pass; ?></td>
	</tr>
	<tr>
		<th><?= t('Theme') ?></th><td><?= $u->theme; ?></td>
	</tr>
</table>
<?php include '../common_templates/closure.php'; ?>
