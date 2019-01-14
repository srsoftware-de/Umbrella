<?php include 'controller.php';

User::require_login();

if ($login = post('login')){ // defined in bootstrap.php
	if ($pass =  post('pass')){
		$u = new User();
		if ($u->patch(['login'=>$login,'pass'=>$pass])->save()) redirect(getUrl('user'));
	} else error('No password given!');
} else if ($pass = post('pass')) error('No username given');

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>
<form method="POST">
	<fieldset><legend><?= t('Create new user') ?></legend>
		<fieldset><legend><?= t('Login Name') ?></legend>
		<input type="text" name="login" />
		</fieldset>
		<fieldset><legend><?= t('Password') ?></legend>
		<input type="password" name="pass" />
		</fieldset>
		<button type="submit"><?= t('Add user') ?></button>
	</fieldset>
</form>

<?php include '../common_templates/closure.php'; ?>
