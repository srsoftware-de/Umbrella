<?php include 'controller.php';

$user = User::require_login();

if ($user_id = param('id')){
	if ($user->id != 1 && $user_id != $user->id){
		error('Currently, only admin can view other users!');
		redirect('../index');
	}

	$u = User::load(['ids'=>$user_id,'passwords'=>'load']);
	if (empty($u)) error('No such user');
} else error('No user id passed to view!');


include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php';
?>
<fieldset>

<legend><?= t('Basic data') ?></legend>
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
