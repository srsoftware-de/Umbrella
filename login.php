<?php include 'controller.php';
$title = 'Umbrella login';

$redirect = param('returnTo');
if ($username = post('username')){ // defined in bootstrap.php
	if ($pass =  post('pass')){
		$users = User::load(['login'=>$username,'passwords'=>'load']);
		foreach ($users as $u){
			if ($u->correct($pass)) $u->login();
			break;
		}
		http_response_code(401);
		sleep(10);
		error('The provided username/password combination is not valid!');
	} else error('No password given!');
} else if ($pass = post('pass')) error('No username given');

$admin = User::load(['ids'=>1,'passwords'=>'load']);
if ($admin->pass == sha1('admin') && $admin->login == 'admin') info(t('The default username/password is admin/admin.'));

$login_services = LoginService::load();
if (!empty($redirect)){
	$allowed = false;
	foreach ($services as $service){
		if (strpos($redirect, $service['path'])===0) {
			$allowed = true;
			break;
		}
	}
	if (isset($_SESSION['token']) && Token::load($_SESSION['token'])) {
		if ($allowed) redirect($redirect.'?token='.$_SESSION['token']);
		redirect($redirect);
	}
}

include '../common_templates/head.php';
include '../common_templates/messages.php';

if (!empty($login_services)) { ?>
<fieldset class="openid">
	<legend><?= t('Login using OAuth 2 / OpenID Connect')?></legend>
	<?php foreach ($login_services as $name => $data) {?>
	<a class="button" title="<?= t('Log in using ◊ account.',$name)?>" href="openid_login?service=<?= $name.($redirect?'&returnTo='.urlencode($redirect):'') ?>"><?= $name ?></a>
	<?php }?>
</fieldset>
<?php } ?>

<form method="POST">
	<fieldset>
		<legend><?= t('Login using username and password')?></legend>
		<fieldset><legend><?= t('Username')?></legend>
		<input type="text" autofocus="autofocus" name="username" />
		</fieldset>
		<fieldset><legend><?= t('Password')?></legend>
		<input type="password" name="pass" />
		</fieldset>
		<input type="submit" />
	</fieldset>
</form>

<?php include '../common_templates/closure.php'; ?>
