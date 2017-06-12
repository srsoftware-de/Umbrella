<?php $title = 'Umbrella login';

include '../bootstrap.php';
include 'controller.php';

if ($email = post('email')){ // defined in bootstrap.php
	if ($pass =  post('pass')){
		$db = get_or_create_db();
		add_user($db,$email,$pass); // defined in controller.php
		header('Location: index');
		die();
	} else error('No password given!');
} else if ($pass = post('pass')) error('No email given');

include '../common_templates/head.php'; 
include '../common_templates/messages.php'; ?>
<form method="POST">
	<fieldset><legend>Login</legend>
		<fieldset><legend>Email</legend>
		<input type="text" name="email" />
		</fieldset>
		<fieldset><legend>Password</legend>
		<input type="password" name="pass" />
		</fieldset>
		<input type="submit" />
	</fieldset>
</form>

<?php include '../common_templates/closure.php'; ?>
