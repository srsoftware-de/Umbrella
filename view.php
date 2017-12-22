<?php

include '../bootstrap.php';
include 'controller.php';

require_login('project');
$project_id = param('id');
if (!$project_id) error('No project id passed to view!');

$project_users_permissions = load_users($project_id);

assert(array_key_exists($user->id, $project_users_permissions),'You are not member of this project!');
$project = load_projects(['ids'=>$project_id,'single'=>true]);

$current_user_is_owner = ($project_users_permissions[$user->id]['permissions'] == PROJECT_PERMISSION_OWNER);

$project_users = null;

if ($remove_user_id = param('remove_user')){
	if ($current_user_is_owner){
		remove_user($project_id,$remove_user_id);
		unset($project_users_permissions[$remove_user_id]);
	} else error('You are not allowed to remove users from this project');
}

if (!empty($project_users_permissions)){
	$user_ids = implode(',',array_keys($project_users_permissions));
	$project_users = request('user', 'list',['ids'=>$user_ids]);
}
//debug($project_users);
$tasks = request('task','list',['order'=>'status','project'=>$project_id]);
//debug($tasks,true);
$title = $project['name'].' - Umbrella';
$show_closed_tasks = param('closed') == 'show';

$companies = request('company','json_list');

if (isset($services['bookmark'])){
	$hash = sha1(location('*'));
	$bookmark = request('bookmark','json_get',['id'=>$hash]);	
}

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
			<span class="hover_h">
			<a class="symbol" title="edit" 			href="../../task/<?= $tid ?>/edit?redirect=../../project/<?= $project_id ?>/view"></a>
			<a class="<?= $task['status'] == TASK_STATUS_STARTED?'hidden':'symbol'?>" title="started"  href="../../task/<?= $tid ?>/start?redirect=../../project/<?= $project_id ?>/view"></a>
			<a class="<?= $task['status'] == TASK_STATUS_COMPLETE?'hidden':'symbol'?>" title="complete" href="../../task/<?= $tid ?>/complete?redirect=../../project/<?= $project_id ?>/view"></a>
			<a class="<?= $task['status'] == TASK_STATUS_CANCELED?'hidden':'symbol'?>" title="cancel"   href="../../task/<?= $tid ?>/cancel?redirect=../../project/<?= $project_id ?>/view"></a>
			<a class="<?= $task['status'] == TASK_STATUS_OPEN?'hidden':'symbol'?>" title="open"     href="../../task/<?= $tid ?>/open?redirect=../../project/<?= $project_id ?>/view"></a>
			<a class="<?= $task['status'] == TASK_STATUS_PENDING?'hidden':'symbol'?>" title="wait"     href="../../task/<?= $tid ?>/wait?redirect=../../project/<?= $project_id ?>/view"></a>
			<a class="symbol" title="add subtask" 	href="../../task/<?= $tid ?>/add_subtask"></a>
			<a class="symbol" title="<?= t('add user') ?>" href="../../task/<?= $tid ?>/add_user"> </a>
			<a class="symbol" title="delete" 		href="../../task/<?= $tid ?>/delete?redirect=../../project/<?= $project_id ?>/view"></a>
			</span>
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
<table class="vertical project-view">
	<tr>
		<th><?= t('Project')?></th>
		<td>
			<span class="right">
				<a class="symbol" title="<?= t('complete')?>" href="complete?redirect=../index"></a>
				<a class="symbol" title="<?= t('cancel')?>" href="cancel?redirect=../index"></a>
				<a class="symbol" title="<?= t('edit') ?>" href="edit"></a>
				<a class="symbol" title="<?= t('add subtask')?>" href="../../task/add_to_project/<?= $project_id ?>"></a>
				<a class="symbol" title="<?= t('add user')?>" href="add_user"></a>
			</span>
			<h1><?= $project['name'] ?></h1>
		</td>
	</tr>
	<tr>
		<?php if (isset($companies[$project['company_id']])) { ?>
		<th><?= t('Company') ?></th>
		<td>
			<?= $companies[$project['company_id']]['name'] ?>
		</td>
		<?php } ?>
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
				<li>
					<?= $u['login'].' ('.t($PROJECT_PERMISSIONS[$project_users_permissions[$uid]['permissions']]).')'; ?>
					<?php if ($current_user_is_owner && $uid != $user->id) { ?><a class="symbol" title="<?= t('remove ? from project',$u['login']) ?>" href="?remove_user=<?= $uid ?>"></a><?php } ?>
				</li>
			<?php } ?>
			</ul>
		</td>
	</tr>
	<?php } ?>
	<?php if ($bookmark) { ?>
	<tr>
		<th><?= t('Tags')?></th>
		<td>
		<?php $base_url = getUrl('bookmark');
		foreach ($bookmark['tags'] as $tag){ ?>
			<a class="button" href="<?= $base_url.'/'.$tag.'/view' ?>"><?= $tag ?></a>
		<?php } ?>
		</td>
	</tr>
	<?php } ?>
</table>
<?php include '../common_templates/closure.php'; ?>
