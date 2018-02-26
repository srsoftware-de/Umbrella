<?php

include '../bootstrap.php';
include 'controller.php';

require_login('task');

if ($key = param('key')){
	$tasks = load_tasks(['key'=>$key]);
	if (!empty($tasks)){ ?>

	<table class="tasks list">
	<tr>
		<th><a href="?order=name"><?= t('Name')?></a></th>
		<th><a href="?order=parent_task_id"><?= t('Parent task')?></a></th>
		<th><a href="?order=project_id"><?= t('Project')?></a></th>
		<th><a href="?order=status"><?= t('Status')?></a></th>
		<th><a href="?order=start_date"><?= t('Start')?></a></th>
		<th><a href="?order=due_date"><?= t('Due')?></a></th>
		<th><?= t('Actions') ?></th>
	</tr>
	
	<?php 
	$hide = [];
	$url = getUrl('task');
	foreach ($tasks as $id => $task):
	$project = $projects[$task['project_id']];
	$parent_id = $task['parent_task_id'];	
	?>
	<tr class="project<?= $task['project_id']?>">
		<td class="<?= task_state($task['status'])?>"><a href="<?= $url.$id ?>/view"><?= $task['name'] ?></a></td>
		<td>
			<?php if ($parent_id !== null && isset($tasks[$parent_id])) { ?>
			<a href="<?= $url.$parent_id ?>/view"><?= $tasks[$parent_id]['name'] ?></a>
			<?php } ?>
		</td>
		<td>
			<span class="hover_h">
			<a href="../project/<?= $task['project_id']?>/view"><?= $project['name'] ?></a>&nbsp;<a href="#" class="symbol" onclick="return toggle('.project<?= $task['project_id'] ?>');"></a>&nbsp;<a href="#" class="symbol" onclick="toggle('tr:not(.project<?= $task['project_id'] ?>)')"></a>
			</span>
		</td>
		<td><?= t(task_state($task['status'])) ?></td>
		<td><?= $task['start_date'] ?></td>
		<td><?= $task['due_date'] ?></td>
		<td>
			<a title="<?= t('edit')?>"     href="<?= $url.$id ?>/edit?redirect=../index"     class="symbol"></a>
			<a title="<?= t('complete')?>" href="<?= $url.$id ?>/complete?redirect=../index" class="<?= $task['status'] == TASK_STATUS_COMPLETE ? 'hidden':'symbol'?>"></a>
			<a title="<?= t('cancel')?>"   href="<?= $url.$id ?>/cancel?redirect=../index"   class="<?= $task['status'] == TASK_STATUS_CANCELED ? 'hidden':'symbol'?>"></a>
			<a title="<?= t('do open')?>"  href="<?= $url.$id ?>/open?redirect=../index"     class="<?= in_array($task['status'],[TASK_STATUS_OPEN,TASK_STATUS_STARTED])? 'hidden':'symbol'?>"></a>
			<a title="<?= t('wait')?>"     href="<?= $url.$id ?>/wait?redirect=../index"	  	class="<?= $task['status'] == TASK_STATUS_PENDING  ? 'hidden':'symbol'?>"></a>
			<a title="<?= t('start')?>  "  href="<?= $url.$id ?>/start?redirect=../index"    class="<?= $task['status'] == TASK_STATUS_STARTED  ? 'hidden':'symbol'?>"></a>
		</td>
	</tr>	
<?php endforeach; ?>
</table>
	<?php }
}