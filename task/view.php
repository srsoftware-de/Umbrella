<?php 

include '../bootstrap.php';
include 'controller.php';

$user = current_user();
$task_id = param('id');

if (!$task_id) error('No task id passed to view!');

$task = load_task($task_id,true);
if ($task['parent_task_id']) $task['parent'] = load_task($task['parent_task_id']);
load_children($task,99); // up to 99 levels deep
$title = $task['name'].' - Umbrella';
function display_children($task){
	if (!isset($task['children'])) return; ?>
	<ul>
	<?php foreach ($task['children'] as $id => $child_task) {?>
		<li><a href="../<?= $id ?>/view"><?= $child_task['name']?></a>
			<?php display_children($child_task);?>
		</li>
	<?php }?>
	</ul>
	<?php
}

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php';
?>
<h1><?= $task['name'] ?></h1>
<table class="vertical">
	<tr>
		<th>Task</th>
		<td><?= $task['name'];?> (
			<a href="open"     <?= $task['status'] == TASK_STATUS_OPEN     ? 'class="emphasized"':''?>>open</a> |
			<a href="complete" <?= $task['status'] == TASK_STATUS_COMPLETE ? 'class="emphasized"':''?>>completed</a> |
			<a href="cancel"   <?= $task['status'] == TASK_STATUS_CANCELED ? 'class="emphasized"':''?>>canceled</a> |
			<a href="wait"	   <?= $task['status'] == TASK_STATUS_PENDING  ? 'class="emphasized"':''?>>pending</a>
		)</td>
	</tr>
	<?php if ($task['parent_task_id']) {?>
	<tr>
		<th>Parent</th>
		<td><a href="../<?= $task['parent_task_id'] ?>/view"><?= $task['parent']['name'];?></a></td>
	</tr>
	<?php }?>
	<tr>
		<th>Description</th>
		<td><pre><?= $task['description']; ?></pre></td>
	</tr>
	<tr>
		<th>Child tasks</th>
		<td><?php display_children($task); ?></td>
	</tr>	
</table>
<?php include '../common_templates/closure.php'; ?>
