<?php $title = 'Umbrella Users';

include '../bootstrap.php';
include 'controller.php';

$user = current_user();

if ($user->id != 1) error('Currently, only admin can view the user list!');

include '../common_templates/head.php'; 

include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php';

if ($user->id == 1){
	$users = get_userlist(); ?>

<table>
	<tr>
		<th>Id</th>
		<th>Username</th>
		<th>Actions</th>
	</tr>
<?php foreach ($users as $id => $u): ?>
	<tr>
		<td><?= $id ?></td>
		<td><?= $u['login'] ?></td>
		<td><a href="<?= $id?>/edit">Edit</a></td>
	</tr>
<?php endforeach; ?>

</table>
<?php }
include '../common_templates/closure.php'; ?>
