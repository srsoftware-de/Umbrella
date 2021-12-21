<?php include 'controller.php';
require_login('task');

$tasks = Task::load(['order'=>param('order','due_date')]);
$projects = request('project','json');
$show_closed = param('closed') == 'show';

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<div class="hover right-fix">
	<table>
		<tr><th>
			<?= t('Hide/Show') ?> (<a href="#" onclick="return toggle('[class^=project_]');"><?= t('all')?></a> | <a href="?closed=show"><?= t('closed')?></a>)
		</th></tr>
		<?php foreach ($projects as $pid => $project){ ?>
		<tr class="plist">
			<td>
			<a href="#" onclick="return toggle('.project<?= $pid ?>');"><?= $project['name']; ?></a>
			</td>
		</tr>
		<?php } ?>
	</table>
</div>
<h2><?= t("Tasks")?></h2>
<table class="tasks list">
	<tr>
		<th><a href="?order=name"><?= t('Name')?></a></th>
		<th hide=6><a href="?order=project_id"><?= t('Project')?></a> / <a href="?order=parent_task_id"><?= t('Parent task')?></a></th>
		<th hide="7"><a href="?order=status"><?= t('Status')?></a></th>
		<th hide="12"><a href="?order=start_date"><?= t('Start')?></a></th>
		<th hide="9"><a href="?order=due_date"><?= t('Due')?></a></th>
		<th><?= t('Actions') ?></th>
	</tr>

<?php
	$hide = [];
	foreach ($tasks as $id => $task){ // filter out tasks, that are only group nodes
		if (!$show_closed && in_array($task->status,[TASK_STATUS_PENDING,TASK_STATUS_COMPLETE,TASK_STATUS_CANCELED])) continue;
		if ($task->no_index == 1) $hide[] = $task->id;
		if (!empty($task->parent_task_id)) $hide[] = $task->parent_task_id; // if task is child of a parent task: hide that parent
	}
	foreach ($tasks as $id => $task){
		if (!$show_closed && in_array($task->status,[TASK_STATUS_PENDING,TASK_STATUS_COMPLETE,TASK_STATUS_CANCELED])) continue;
		if (in_array($id, $hide)) continue;
		$project = $projects[$task->project_id];
		if (in_array($project['status'], [PROJECT_STATUS_CANCELED,PROJECT_STATUS_COMPLETE])) continue;
	?>
	<tr class="project<?= $task->project_id ?>">
		<td class="<?= task_state($task->status)?>"><a href="<?= $id ?>/view"><?= $task->name ?></a></td>
		<td hide="6">
			<span class="hover_h">
			<a href="../project/<?= $task->project_id ?>/view"><?= $project['name'] ?></a><a href="#" class="symbol" onclick="return toggle('.project<?= $task->project_id ?>');">&nbsp;</a><a href="#" class="symbol" onclick="toggle('tr:not(.project<?= $task->project_id ?>)')">&nbsp;</a>
			</span>
			<?php if ($task->parent_task_id !== null && isset($tasks[$task->parent_task_id])) { ?>
			: <a href="../task/<?= $task->parent_task_id ?>/view"><?= $tasks[$task->parent_task_id]->name ?></a>
			<?php } ?>
		</td>
		<td hide="7"><?= t(task_state($task->status)) ?></td>
		<td hide="12"><?= $task->start_date ?></td>
		<td hide="9"><?= $task->due_date ?></td>
		<td>
			<a title="<?= t('edit')?>"     href="<?= $id ?>/edit?redirect=../index"     class="symbol"></a>
			<a title="<?= t('complete')?>" href="<?= $id ?>/complete?redirect=../index" class="<?= $task->status == TASK_STATUS_COMPLETE ? 'hidden':'symbol'?>"></a>
			<a title="<?= t('cancel')?>"   href="<?= $id ?>/cancel?redirect=../index"   class="<?= $task->status == TASK_STATUS_CANCELED ? 'hidden':'symbol'?>"></a>
			<a title="<?= t('do open')?>"  href="<?= $id ?>/open?redirect=../index"     class="<?= in_array($task->status,[TASK_STATUS_OPEN,TASK_STATUS_STARTED])? 'hidden':'symbol'?>"></a>
			<a title="<?= t('wait')?>"     href="<?= $id ?>/wait?redirect=../index"	  	class="<?= $task->status == TASK_STATUS_PENDING  ? 'hidden':'symbol'?>"></a>
			<a title="<?= t('start')?>  "  href="<?= $id ?>/start?redirect=../index"    class="<?= $task->status == TASK_STATUS_STARTED  ? 'hidden':'symbol'?>"></a>
		</td>
	</tr>
<?php }; ?>

</table>
<?php include '../common_templates/closure.php'; ?>
