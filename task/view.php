<?php 

include '../bootstrap.php';
include 'controller.php';

$user = current_user();
$task_id = param('id');

if (!$task_id) error('No task id passed to view!');

$task = load_tasks($task_id);
if ($task['parent_task_id']) $task['parent'] = load_tasks($task['parent_task_id']);
load_children($task,99); // up to 99 levels deep
load_requirements($task);

$project_users_permissions = request('project','user_list?id='.$task['project_id']); // needed to load project users
$project_users = request('user','list?ids='.implode(',', array_keys($project_users_permissions))); // needed to load task users
load_users($task,$project_users);
//debug($task);
$title = $task['name'].' - Umbrella';
$task['project'] = request('project','json?id='.$task['project_id']);
$show_closed_children = param('closed') == 'show';

function display_children($task){
	global $show_closed_children,$task_id;
	if (!isset($task['children'])) return; ?>
	<ul>
	<?php foreach ($task['children'] as $id => $child_task) {
			if (!$show_closed_children && $child_task['status'] >= 60) continue;
		?>
		<li class="<?= $child_task['status_string'] ?>">
			<a href="../<?= $id ?>/view"><?= $child_task['name']?></a>
			<a title="started"  href="../<?= $id ?>/start?redirect=../<?= $task_id ?>/view"    class="<?= $task['status'] == TASK_STATUS_STARTED  ? 'hidden':'symbol'?>"></a> 
			<a title="complete" href="../<?= $id ?>/complete?redirect=../<?= $task_id ?>/view" class="<?= $task['status'] == TASK_STATUS_COMPLETE ? 'hidden':'symbol'?>"></a>
			<a title="cancel"   href="../<?= $id ?>/cancel?redirect=../<?= $task_id ?>/view"   class="<?= $task['status'] == TASK_STATUS_CANCELED ? 'hidden':'symbol'?>"></a>
			<a title="open"     href="../<?= $id ?>/open?redirect=../<?= $task_id ?>/view"     class="<?= $task['status'] == TASK_STATUS_OPEN     ? 'hidden':'symbol'?>"></a>
			<a title="wait"     href="../<?= $id ?>/wait?redirect=../<?= $task_id ?>/view"	   class="<?= $task['status'] == TASK_STATUS_PENDING  ? 'hidden':'symbol'?>"></a>
			<a title="edit"     href="../<?= $id ?>/edit?redirect=../<?= $task_id ?>/view"     class="symbol"></a>
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
<table class="vertical tasks">
	<tr>
		<th>Project</th>
		<td class="project">
			<span class="right">
				<a title="started"  href="start"    class="<?= $task['status'] == TASK_STATUS_STARTED  ? 'hidden':'symbol'?>"></a> 
				<a title="complete" href="complete" class="<?= $task['status'] == TASK_STATUS_COMPLETE ? 'hidden':'symbol'?>"></a>
				<a title="cancel"   href="cancel"   class="<?= $task['status'] == TASK_STATUS_CANCELED ? 'hidden':'symbol'?>"></a>
				<a title="open"     href="open"     class="<?= $task['status'] == TASK_STATUS_OPEN     ? 'hidden':'symbol'?>"></a>
				<a title="wait"     href="wait"     class="<?= $task['status'] == TASK_STATUS_PENDING  ? 'hidden':'symbol'?>"></a>
				<a title="delete"   href="delete"   class="symbol"></a>

			</span>
			<a href="<?= getUrl('project',$task['project_id'].'/view'); ?>"><?= $task['project']['name']?></a>
			<?php if (isset($services['time'])) { ?>
			<a class="symbol" href="<?= getUrl('time','add_task?id='.$task_id); ?>"></a>
			<?php } ?>
		</td>
	</tr>
	<?php if ($task['parent_task_id']) { ?>
	<tr>
		<th>Parent</th>
		<td><a href="../<?= $task['parent_task_id'] ?>/view"><?= $task['parent']['name'];?></a></td>
	</tr>
	<?php }?>
	<?php if ($task['description']){ ?>
	<tr>
		<th>Description</th>
		<td><pre><?= $task['description']; ?></pre></td>
	</tr>
	<?php } ?>
	<?php if ($task['start_date']) { ?>
	<tr>
		<th>Start</th>
		<td><?= $task['start_date'] ?></td>
	</tr>
	<?php } ?>
	<?php if ($task['due_date']) { ?>
	<tr>
		<th>Due</th>
		<td><?= $task['due_date'] ?></td>
	</tr>
	<?php } ?>
	<?php if (isset($task['requirements'])) { ?>
	<tr>
		<th>Prerequisites</th>
		<td class="requirements">
			<ul>
			<?php foreach ($task['requirements'] as $id => $required_task) {?>
				<li <?= in_array($required_task['status'],array(TASK_STATUS_CANCELED,TASK_STATUS_COMPLETE))?'class="inactive"':''?>><a href="../<?= $id ?>/view"><?= $required_task['name']?></a></li>
			<?php } ?>
			</ul>
		</td>
	</tr>
	<?php } ?>
	<?php if (isset($task['children'])){?>
	<tr>
		<th>Child tasks</th>
		<td class="children">
			<?php if (!$show_closed_children) {?>
			<a href="?closed=show">show closed child tasks</a>
			<?php } ?>
			<?php display_children($task); ?>
		</td>
	</tr>
	<?php } ?>
	<?php if (isset($task['users']) && !empty($task['users'])){ ?>
	<tr>
		<th>Users</th>
		<td>
			<ul>
			<?php foreach ($task['users'] as $uid => $u) { ?>
				<li><?= $u['login'].' ('.$TASK_PERMISSIONS[$u['permissions']].')'; ?></li>
			<?php } ?>
			</ul>
		</td>
	</tr>
	<?php } ?>	
</table>
<?php include '../common_templates/closure.php'; ?>
