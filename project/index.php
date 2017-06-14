<?php $title = 'Umbrella Users';

include '../bootstrap.php';
include 'controller.php';

require_login();
$projects = get_project_list();

include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<table>
	<tr>
		<th>Name</th>
		<th>Status</th>
	</tr>
<?php foreach ($projects as $project): ?>
	<tr>
		<td><a href="<?= $project['id']?>/view"><?= $project['name'] ?></a></td>
		<td><?= $project['status'] ?></td>
		<td><a href="<?= $project['id']?>/edit">Edit</a></td>
	</tr>
<?php endforeach; ?>

</table>
<?php 
include '../common_templates/closure.php'; ?>
