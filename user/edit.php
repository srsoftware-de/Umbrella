<?php $title = 'Umbrella Users';

include '../bootstrap.php';
include 'controller.php';

require_user_login();
$user_id = param('id');

$allowed = ($user->id == 1 || $user->id == $user_id);
if (!$allowed) error('Currently, only admin can edit other users!');

$u = load_user($user_id);
if ($new_pass = post('new_pass')){
	alter_password($u,$new_pass);
	$u = load_user($user_id);
}

include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php';

if ($allowed){
?>
<form method="POST">
	<fieldset>
	<legend>login</legend><?= $u->login; ?>
	</fieldset>
	
	<?php foreach ($u as $key => $val):
	if ($key == 'id' || $key == 'pass' || $key = 'login')continue;?>
	<fieldset>
		<legend><?= $key?></legend><?= $val; ?>
	</fieldset>
	<?php endforeach;?>
	<fieldset>
		<legend>new password</legend>
		<input type="password" name="new_pass" />
	</fieldset>
	<button type=submit">Submit</button>
</form>
<?php }
 include '../common_templates/closure.php'; ?>
