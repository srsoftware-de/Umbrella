<?php $title = 'Umbrella Users';

include '../bootstrap.php';
include 'controller.php';

$user = current_user();
$tasks = get_task_list();

include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<table>
	<tr>
		<th>Name</th>
		<th>Project</th>
		<th>Status</th>
		<th>Actions</th>
	</tr>
<?php foreach ($tasks as $task): ?>
	<tr>
		<td><a href="<?= $task['id']?>/view"><?= $task['name'] ?></a></td>
		<td><a href="../project/<?= $task['project_id']?>/view"><?= $task['project_id'] ?></a></td>
		<td><?= $task['status'] ?></td>
		<td><a href="<?= $task['id']?>/edit">Edit</a></td>
	</tr>
<?php endforeach; ?>

</table>
<?php 
include '../common_templates/closure.php'; ?>
