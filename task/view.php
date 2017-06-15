<?php 

include '../bootstrap.php';
include 'controller.php';

$user = current_user();
$task_id = param('id');

if (!$task_id) error('No task id passed to view!');

$task = load_task($task_id);
$title = $task['name'].' - Umbrella';

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php';
?>
<h1><?= $task['name'] ?></h1>
<table class="vertical">
	<tr>
		<th>Task</th>
		<td><?= $task['name'];?></td>
	</tr>
	<tr>
		<th>Description</th>
		<td><pre><?= $task['description']; ?></pre></td>
	</tr>	
</table>
<?php include '../common_templates/closure.php'; ?>
