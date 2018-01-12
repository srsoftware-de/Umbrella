<?php

include '../bootstrap.php';
include 'controller.php';

require_login('project');
$show_confirm_question = false;
if ($project_id = param('id')){
	$project = load_projects(['ids'=>$project_id,'single'=>true]);	
	if ($project){
		$user_ids = load_users($project);
		$current_user_is_owner = $project['users'][$user->id]['permissions'] == PROJECT_PERMISSION_OWNER;
		
		if ($remove_user_id = param('remove_user')){
			if ($current_user_is_owner){
				if (param('confirm')==='yes'){
					remove_user($project_id,$remove_user_id);
					unset($project['users'][$remove_user_id]);
				} else {
					$show_confirm_question = true;
				}
			} else error('You are not allowed to remove users from this project');
		}
		
		$users = request('user','json',['ids'=>$user_ids]);
		$tasks = request('task','json',['order'=>'name','project_ids'=>$project_id]);
		
		if ($project['company_id'] !== null && isset($services['company'])){
			$project['company'] = request('company','json',['ids'=>$project['company_id'],'single'=>true]);
		}
		
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
			<a class="symbol" title="<?= t('edit') ?>" href="../../task/<?= $tid ?>/edit?redirect=../../project/<?= $project_id ?>/view"></a>
			<a class="symbol" title="<?= t('add subtask') ?>" 	href="../../task/<?= $tid ?>/add_subtask"> </a>
			<?php if ($task['status'] != TASK_STATUS_STARTED) { ?>
			<a class="symbol" title="<?= t('started') ?>"  href="../../task/<?= $tid ?>/start?redirect=../../project/<?= $project_id ?>/view"></a>
			<?php } if ($task['status'] != TASK_STATUS_COMPLETE) { ?>
			<a class="symbol" title="<?= t('complete') ?>" href="../../task/<?= $tid ?>/complete?redirect=../../project/<?= $project_id ?>/view"></a>
			<?php } if ($task['status'] != TASK_STATUS_CANCELED) { ?>
			<a class="symbol" title="<?= t('cancel') ?>"   href="../../task/<?= $tid ?>/cancel?redirect=../../project/<?= $project_id ?>/view"></a>
			<?php } if ($task['status'] != TASK_STATUS_OPEN) { ?>
			<a class="symbol" title="<?= t('open') ?>"     href="../../task/<?= $tid ?>/open?redirect=../../project/<?= $project_id ?>/view"></a>
			<?php } if ($task['status'] != TASK_STATUS_PENDING) { ?>
			<a class="symbol" title="<?= t('wait') ?>"     href="../../task/<?= $tid ?>/wait?redirect=../../project/<?= $project_id ?>/view"></a>
			<?php } ?>
			<a class="symbol" title="<?= t('add user') ?>" href="../../task/<?= $tid ?>/add_user"> </a>
			<a class="symbol" title="<?= t('delete') ?>"   href="../../task/<?= $tid ?>/delete?redirect=../../project/<?= $project_id ?>/view"></a>
			</span>
			<?php display_tasks($task_list,$tid)?>
		</li>
		<?php
	}
	if (!$first){
		?></ul><?php
	}
}

$est_time = 0;
foreach ($tasks as $task) $est_time += $task['est_time'];

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php';

if ($project){
	if ($show_confirm_question){ ?>
<fieldset>
	<legend><?= t('Confirm removal of "?" from project?',$users[$remove_user_id]['login'])?></legend>
	<?= t('User will no longer have access to this projects. Task assigned to ? will be assigned to you. Are you sure?',$users[$remove_user_id]['login'])?><br/>
	<a class="button" href="?remove_user=<?= $remove_user_id?>&confirm=yes"><?= t('Yes')?></a>
	<a class="button" href="view"><?= t('No')?></a>
</fieldset>
<?php }
?>
<table class="vertical project-view">
	<tr>
		<th><?= t('Project')?></th>
		<td>
			<span class="right">
				<a class="symbol" title="<?= t('complete')?>" href="complete?redirect=../index"></a>
				<a class="symbol" title="<?= t('cancel')?>" href="cancel?redirect=../index"></a>
				<a class="symbol" title="<?= t('edit') ?>" href="edit"></a>
				<a class="symbol" title="<?= t('add subtask')?>" href="../../task/add_to_project/<?= $project_id ?>"> </a>
				<a class="symbol" title="<?= t('add user')?>" href="add_user"></a>
			</span>
			<h1><?= $project['name'] ?></h1>
		</td>
	</tr>
	<?php if (isset($project['company'])) { ?>
	<tr>
		<th><?= t('Company') ?></th>
		<td><?= $project['company']['name'] ?></td>
	</tr>
	<?php } ?>
	<tr>
		<th><?= t('Description')?></th><td><?= $project['description']; ?></td>
	</tr>
	<?php if ($est_time) { ?>
	<tr>
		<th><?= t('Estimated time')?></th><td><?= t('? hours',$est_time) ?></td>
	</tr>
	<?php } ?>
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
