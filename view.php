<?php

include '../bootstrap.php';
include 'controller.php';

require_login('project');
if ($project_id = param('id')){
	$project = load_projects(['ids'=>$project_id,'single'=>true]);	
	if ($project){
		$user_ids = load_users($project);
		$current_user_is_owner = $project['users'][$user->id]['permissions'] == PROJECT_PERMISSION_OWNER;
		
		if ($remove_user_id = param('remove_user')){
			if ($current_user_is_owner){
				remove_user($project_id,$remove_user_id);
				unset($project['users'][$remove_user_id]);
			} else error('You are not allowed to remove users from this project');
		}
		
		$users = request('user','list',['ids'=>implode(',',$user_ids)]);
		$tasks = request('task','json',['order'=>'name','project_ids'=>$project_id]);
		$companies = request('company','json');
		$title = $project['name'].' - Umbrella';
		$show_closed_tasks = param('closed') == 'show';
		
		if (file_exists('../lib/parsedown/Parsedown.php')){
			include '../lib/parsedown/Parsedown.php';
			$project['description'] = Parsedown::instance()->parse($project['description']);
		} else {
			$project['description'] = str_replace("\n", "<br/>", $project['description']);
		}
	} else error('You are not member of this project!');
} else error('No project id passed to view!');


function display_tasks($task_list,$parent_task_id){
	global $show_closed_tasks,$project_id;
	$first = true;
	foreach ($task_list as $tid => $task){
		if (!$show_closed_tasks && ($task['status']>=60)) continue;
		if ($task['parent_task_id'] != $parent_task_id) continue;
		if ($first){
			$first = false; ?><ul><?php
		} ?>
		<li class="<?= task_state($task['status'])?>">
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

if ($project){
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
	<?php if ($project['users']){ ?>
	<tr>
		<th><?= t('Users')?></th>
		<td>
			<ul>
			<?php foreach ($project['users'] as $uid => $perms) { ?>
				<li>
					<?= $users[$uid]['login'].' ('.t($PROJECT_PERMISSIONS[$perms['permissions']]).')'; ?>
					<?php if ($current_user_is_owner && $uid != $user->id) { ?><a class="symbol" title="<?= t('remove ? from project',$users[$uid]['login']) ?>" href="?remove_user=<?= $uid ?>"></a><?php } ?>
				</li>
			<?php } ?>
			</ul>
		</td>
	</tr>
	<?php } ?>		
</table>
<?php 
if (isset($services['bookmark'])) echo request('bookmark','html',['hash'=>sha1(location('*'))],false,NO_CONVERSSION); 
if (isset($services['notes'])) echo request('notes','html',['uri'=>'project:'.$project_id],false,NO_CONVERSSION);
}
include '../common_templates/closure.php'; ?>
