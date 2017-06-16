<?php $title = 'Umbrella Users';

include '../bootstrap.php';
include 'controller.php';

require_login();
$projects = get_project_list(param('order'));

include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<table>
	<tr>
		<th><a href="?order=name">Name</a></th>
		<th><a href="?order=status">Status</a></th>
	</tr>
<?php foreach ($projects as $id => $project): ?>
	<tr>
		<td><a href="<?= $id ?>/view"><?= $project['name'] ?></a></td>
		<td><?= $PROJECT_STATES[$project['status']] ?></td>
		<td>
			<a href="<?= $id ?>/edit">Edit</a>
			<a href="<?= $id ?>/open">Open</a>
			<a href="<?= $id ?>/complete">Complete</a>
			<a href="<?= $id ?>/cancel">Cancel</a>
		</td>
	</tr>
<?php endforeach; ?>

</table>
<?php 
include '../common_templates/closure.php'; ?>
