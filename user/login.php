<?php $title = 'Umbrella login';

include '../bootstrap.php';
include 'controller.php';

if ($email = post('email')){ // defined in bootstrap.php
	if ($pass =  post('pass')){
		perform_login($email,$pass); // defined in controller.php
	} else error('No password given!');
} else if ($pass = post('pass')) error('No email given');

$admin = load_user(1);
if ($admin->pass == sha1('admin') && $admin->login == 'admin') info(t('The default username/password is admin/admin.'));

$login_services = get_login_services();

$redirect = param('returnTo');

include '../common_templates/head.php'; 
include '../common_templates/messages.php'; 

if (!empty($login_services)) { ?>
<fieldset>
	<legend><?= t('Login using OAuth 2 / OpenID Connect')?></legend>
	<?php foreach ($login_services as $name => $data) {?>
	<a class="button" title="<?= t('Log in using ? account.',$name)?>" href="openid_login?service=<?= $name.($redirect?'&returnTo='.urlencode($redirect):'') ?>"><?= $name ?></a>
	<?php }?>
</fieldset>
<?php } ?>
<?php include '../common_templates/closure.php'; ?>


<form method="POST">
	<fieldset><legend><?= t('Login using email and password')?></legend>
		<fieldset><legend><?= t('Email')?></legend>
		<input type="text" autofocus="true" name="email" />
		</fieldset>
		<fieldset><legend><?= t('Password')?></legend>
		<input type="password" name="pass" />
		</fieldset>
		<input type="submit" />
	</fieldset>
</form>

