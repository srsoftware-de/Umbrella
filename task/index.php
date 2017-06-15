<?php $title = 'Umbrella Users';

include '../bootstrap.php';
include 'controller.php';

$user = current_user();
$tasks = get_task_list(param('order'));
$projects = request('project','list');

include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<table>
	<tr>
		<th><a href="?order=name">Name</a></th>
		<th><a href="?order=project_id">Project</a></th>
		<th><a href="?order=parent_task_id">Parent Task</a></th>
		<th><a href="?order=status">Status</a></th>
		<th>Actions</th>
	</tr>
	
<?php foreach ($tasks as $id => $task):
	$project = $projects[$task['project_id']];
	$parent_id = $task['parent_task_id'];
	?>
	<tr>
		<td><a href="<?= $id ?>/view"><?= $task['name'] ?></a></td>
		<td><a href="../project/<?= $task['project_id']?>/view"><?= $project['name'] ?></a></td>
		<td><?php if ($parent_id !== null) { ?><a href="../task/<?= $parent_id ?>/view"><?= $tasks[$parent_id]['name'] ?></a><?php } ?></td>
		<td><?= $task_states[$task['status']] ?></td>
		<td>
			<a href="<?= $id ?>/edit">Edit</a>
			<a href="<?= $id ?>/add_subtask">Add subtask</a>
			<a href="<?= $id ?>/complete">Complete</a>
			<a href="<?= $id ?>/cancel">Cancel</a>
		</td>
	</tr>
<?php endforeach; ?>

</table>
<?php 
include '../common_templates/closure.php'; ?>
