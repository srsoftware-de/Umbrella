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
</fieldset>

<fieldset>
	<legend><?= t('Foreign logins')?></legend>
	<table>
		<tr>
			<th><?= t('Service')?></th>
			<th><?= t('Domain')?></th>
			<th><?= t('Credentials')?></th>
		</tr>
		<?php foreach ($foreign_services as $fs) { ?>
		<tr>
			<td><a target="_blank" href="https://<?=  $fs->domain ?>/login?umbrella_token=<?= $_SESSION['token']  ?>"><?= $fs->domain ?></a></td>
			<td><?= $fs->domain ?></td>
			<td><?= empty($fs->credentials) ? add_button($fs) : credentials($fs); ?></td>
		</tr>
		<?php } // foreach foreign service $fs?>
	</table>
</fieldset>
<?php include '../common_templates/closure.php'; ?>
