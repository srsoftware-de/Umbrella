<?php 

include '../bootstrap.php';
include 'controller.php';

$user = current_user();
$project_id = param('id');

if (!$project_id) error('No project id passed to view!');

$project = load_project($project_id);
$project_users_permissions = load_users($project_id);
$project_users = null;
if (!empty($project_users_permissions)){
	$user_ids = implode(',',array_keys($project_users_permissions));
	$project_users = request('user', 'list?ids='.$user_ids);
}
$tasks = request('task','list?order=status&project='.$project_id);
$title = $project['name'].' - Umbrella';
include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php';
?>
<h1><?= $project['name'] ?></h1>
<table class="vertical">
	<tr>
		<th>Project</th><td><?= $project['name'];?></td>
	</tr>
	<tr>
		<th>Description</th><td><?= $project['description']; ?></td>
	</tr>
	<?php if ($tasks) {?>
	<tr>
		<th>Tasks</th>
		<td class="tasks">
			<ul>
			<?php foreach ($tasks as $tid => $task) {
				if ($task['status']>=60) continue; ?>
				<li <?= $task['status']>=40?'class="pending"':'' ?>><a href="<?= getUrl('task', $tid.'/view'); ?>"><?= $task['name'] ?></a></li>
			<?php } ?>
			</ul>
		</td>
	</tr>
	<?php } ?>
	<?php if ($project_users){ ?>
	<tr>
		<th>Users</th>
		<td>
			<ul>
			<?php foreach ($project_users as $uid => $u) { ?>
				<li><?= $u['login'].' ('.$PROJECT_PERMISSIONS[$project_users_permissions[$uid]['permissions']].')'; ?></li>
			<?php } ?>
			</ul>
		</td>
	</tr>
	<?php } ?>	
</table>
<?php include '../common_templates/closure.php'; ?>
