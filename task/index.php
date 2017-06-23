<?php $title = 'Umbrella Task Management';

include '../bootstrap.php';
include 'controller.php';

$user = current_user();
$tasks = get_task_list(param('order'));
//debug($tasks,true);
$projects = request('project','list');
$show_closed = param('closed') == 'show';

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
		<th><a href="?order=start_date">Start</a></th>
		<th><a href="?order=due_date">Due</a></th>
		<th>Actions</th>
	</tr>
	
<?php foreach ($tasks as $id => $task):
	if ($task['status'] >= 60 && !$show_closed) continue;
	$project = $projects[$task['project_id']];
	$parent_id = $task['parent_task_id'];
	?>
	<tr>
		<td><a href="<?= $id ?>/view"><?= $task['name'] ?></a></td>
		<td><a href="../project/<?= $task['project_id']?>/view"><?= $project['name'] ?></a></td>
		<td><?php if ($parent_id !== null) { ?><a href="../task/<?= $parent_id ?>/view"><?= $tasks[$parent_id]['name'] ?></a><?php } ?></td>
		<td><?= $task_states[$task['status']] ?></td>
		<td><?= $task['start_date'] ?></td>
		<td><?= $task['due_date'] ?></td>
		<td>
			<a href="<?= $id ?>/edit">Edit</a>
			<a href="<?= $id ?>/add_subtask">Add subtask</a>
			<a href="<?= $id ?>/complete?returnto=..">Complete</a>
			<a href="<?= $id ?>/cancel?returnto=..">Cancel</a>
		</td>
	</tr>
<?php endforeach; ?>

</table>
<?php 
include '../common_templates/closure.php'; ?>
