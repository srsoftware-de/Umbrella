<?php 

include '../bootstrap.php';
include 'controller.php';

$user = current_user();
$user_id = param('id');

if ($user->id != 1) {
	if ($user_id != $user_id) error('Currently, only admin can view other users!');
}

$u = load_user($user_id);
$title = $u['login'].' - Umbrella';

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php';
?>
<h1><?= $u['login'] ?></h1>
<table class="vertical">
	<tr>
		<th>Username</th><td><?= $u['login'];?></td>
	</tr>
	<tr>
		<th>Password (hashed)</th><td><?= $u['pass']; ?></td>
	</tr>	
</table>
<?php include '../common_templates/closure.php'; ?>
