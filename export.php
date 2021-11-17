<?php include 'controller.php';

require_login('project');

$show_confirm_question = false;

if ($project_id = param('id')){
	$project = Project::load(['ids'=>$project_id,'users'=>true]);
	if ($project){
		$current_user_is_owner = $project->users[$user->id]['permission'] == PROJECT_PERMISSION_OWNER;
		$tasks = request('task','json',['order'=>'name','project_ids'=>$project_id]);

		if ($project->company_id > 0 && isset($services['company'])) $project->company = request('company','json',['ids'=>$project->company_id]);

		$title = t('Umprella: Project ◊',$project->name);
		$show_closed_tasks = param('closed') == 'show';

		header('Content-Disposition: attachment; filename="'.$project->name.'.html"');
	} else error('You are not member of this project!');
} else error('No project id passed to view!');

function display_tasks($task_list,$parent_task_id){
	global $show_closed_tasks,$project_id,$services;
	$first = true;
	foreach ($task_list as $tid => $task){
		if (!$show_closed_tasks && ($task['status']>=60)) continue;
		if ($task['parent_task_id'] != $parent_task_id) continue;
		if ($first){
			$first = false; ?><ul><?php
		} ?>
		<li class="<?= task_state($task['status'])?>">
			<h1><a href="<?= getUrl('task', $tid.'/view'); ?>"><?= $task['name'] ?></a>
			<?php if (isset($task['est_time']) && $task['est_time']>0) { ?>
			(<?= $task['est_time']?>&nbsp;h)
			<?php } ?></h1>
			<?php if (!empty($task['description'])) { ?>
    			<fieldset>
    			<?= markdown($task['description']); ?>
    			</fieldset>
    			<?php
    			if (isset($services['notes'])) echo request('notes','html',['uri'=>'task:'.$task['id'],'form'=>false],false,NO_CONVERSION);
			}
			display_tasks($task_list,$tid)?>
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

if ($project){ ?>
<table class="vertical project-view">
	<tr>
		<th><?= t('Project')?></th>
		<td>
			<h1><?= $project->name ?></h1>
		</td>
	</tr>
	<?php if (isset($project->company)) { ?>
	<tr>
		<th><?= t('Company') ?></th>
		<td><a href="<?=getUrl('company')?>"><?= $project->company['name'] ?></a></td>
	</tr>
	<?php } ?>
	<tr>
		<th><?= t('Description')?></th><td><?= markdown($project->description) ?></td>
	</tr>
	<?php if ($est_time) { ?>
	<tr>
		<th><?= t('Estimated time')?></th><td><?= t('◊ hours',$est_time) ?></td>
	</tr>
	<?php } ?>
	<tr>
		<th>
			<?= t('Tasks')?>
		</th>
		<td class="tasks">
			<?php if ($tasks) {
				display_tasks($tasks, null);
			} else { ?>
			<a class="symbol" href="<?= getUrl('task','add_to_project/'.$project->id) ?>"></a>
			<a href="<?= getUrl('task','add_to_project/'.$project->id) ?>"><?= t('add task') ?></a>
			<?php } ?>
		</td>
	</tr>
	<?php if ($project->users){ ?>
	<tr>
		<th><?= t('Users')?></th>
		<td>
			<ul>
			<?php foreach ($project->users as $uid => $usr) { ?>
				<li>
					<?= $usr['data']['login'].' ('.t($PROJECT_PERMISSIONS[$usr['permission']]).')'; ?>
				</li>
			<?php } ?>
			</ul>
		</td>
	</tr>
	<?php } ?>
</table>
<?php
if (isset($services['notes'])) echo request('notes','html',['uri'=>'project:'.$project_id,'form'=>false],false,NO_CONVERSION);
}
include '../common_templates/closure.php'; ?>
