<?php $title = 'Umbrella Users';

include '../bootstrap.php';
include 'controller.php';

$user = current_user();

if ($user->id != 1) error('Currently, only admin can view the user list!');

include '../common_templates/head.php'; 

include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php';

	$projects = get_project_list(); ?>

<table>
	<tr>
		<th>Name</th>
		<th>Status</th>
	</tr>
<?php foreach ($projects as $project): ?>
	<tr>
		<td><?= $project['name'] ?></td>
		<td><?= $project['status'] ?></td>
		<td><a href="edit?id=<?= $project['id']?>">Edit</a></td>
	</tr>
<?php endforeach; ?>

</table>
<?php 
include '../common_templates/closure.php'; ?>
