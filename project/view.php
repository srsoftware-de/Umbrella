<?php 

include '../bootstrap.php';
include 'controller.php';

require_login();
$project_id = param('id');
if (!$project_id) error('No project id passed to view!');

$project = load_projects($project_id);
//debug($project);
$project_users_permissions = load_users($project_id);
//debug($project_users_permissions);
$project_users = null;
if (!empty($project_users_permissions)){
	$user_ids = implode(',',array_keys($project_users_permissions));
	$project_users = request('user', 'list',['ids'=>$user_ids]);
}
//debug($project_users);
$tasks = request('task','list',['order'=>'status','project'=>$project_id]);
//debug($tasks,true);
$title = $project['name'].' - Umbrella';
$show_closed_tasks = param('closed') == 'show';

function display_tasks($task_list,$parent_task_id){
	global $show_closed_tasks,$project_id;
	$first = true;
	foreach ($task_list as $tid => $task){		
		if (!$show_closed_tasks && ($task['status']>=60)) continue;
		if ($task['parent_task_id'] != $parent_task_id) continue;
		if ($first){
			$first = false; ?><ul><?php
		} ?>
		<li class="<?= $task['status_string']?>">
			<a href="<?= getUrl('task', $tid.'/view'); ?>"><?= $task['name'] ?></a>
			<a class="symbol" title="edit" 			href="../../task/<?= $tid ?>/edit"></a>
			<a class="<?= $task['status'] == TASK_STATUS_STARTED?'hidden':'symbol'?>" title="started"  href="../../task/<?= $tid ?>/start?redirect=../../project/<?= $project_id ?>/view"></a> 
			<a class="<?= $task['status'] == TASK_STATUS_COMPLETE?'hidden':'symbol'?>" title="complete" href="../../task/<?= $tid ?>/complete?redirect=../../project/<?= $project_id ?>/view"></a>
			<a class="<?= $task['status'] == TASK_STATUS_CANCELED?'hidden':'symbol'?>" title="cancel"   href="../../task/<?= $tid ?>/cancel?redirect=../../project/<?= $project_id ?>/view"></a>
			<a class="<?= $task['status'] == TASK_STATUS_OPEN?'hidden':'symbol'?>" title="open"     href="../../task/<?= $tid ?>/open?redirect=../../project/<?= $project_id ?>/view"></a>
			<a class="<?= $task['status'] == TASK_STATUS_PENDING?'hidden':'symbol'?>" title="wait"     href="../../task/<?= $tid ?>/wait?redirect=../../project/<?= $project_id ?>/view"></a>
			<a class="symbol" title="add subtask" 	href="../../task/<?= $tid ?>/add_subtask"></a>
			<a class="symbol" title="delete" 		href="../../task/<?= $tid ?>/delete?redirect=../../project/<?= $project_id ?>/view"></a>
			<?php display_tasks($task_list,$tid)?>
		</li>
		<?php		
	}
	if (!$first){
		?></ul><?php 
	}
}


include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php';
?>
<table class="vertical">
	<tr>
		<th><?= t('Project')?></th>
		<td>
			<span class="right">
				<a class="symbol" href="complete?redirect=../index"></a>
				<a class="symbol" href="cancel?redirect=../index"></a>
				<a class="symbol" title="edit" href="edit"></a>
				<a class="symbol" title="add subtask" href="../../task/<?= $tid ?>/add_to_project/<?= $project_id ?>"></a>
				<a class="symbol" title="add user" href="add_user"></a>
			</span>
			<h1><?= $project['name'] ?></h1>
		</td>
	</tr>
	<tr>
		<th><?= t('Description')?></th><td><?= $project['description']; ?></td>
	</tr>
	<?php if ($tasks) {?>
	<tr>
		<th><?= t('Tasks')?></th>
		<td class="tasks">
			<?php if (!$show_closed_tasks) { ?>
			<a href="?closed=show"><?= t('show closed tasks')?></a>
			<?php } ?>
			<?php display_tasks($tasks, null); ?>			
		</td>
	</tr>
	<?php } ?>
	<?php if ($project_users){ ?>
	<tr>
		<th><?= t('Users')?></th>
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
