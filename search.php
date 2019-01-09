<?php include 'controller.php';
require_login('task');

if ($key = param('key')){
	$tasks = Task::load(['key'=>$key]);
	if (!empty($tasks)){
		$project_ids = [];
		foreach ($tasks as $task) $project_ids[$task->project_id] = true;
		$projects = request('project','json',['ids'=>array_keys($project_ids)]); ?>
	<table class="tasks list">
	<tr>
		<th><?= t('Name')?></th>
		<th><?= t('Parent task')?></th>
		<th><?= t('Project')?></th>
		<th><?= t('Status')?></th>
		<th><?= t('Start')?></th>
		<th><?= t('Due')?></th>
		<th><?= t('Actions') ?></th>
	</tr>

	<?php
	$url = getUrl('task');
	foreach ($tasks as $id => $task){
		$project = $projects[$task->project_id];
		$parent_id = $task->parent_task_id;
		?>
		<tr class="project<?= $task->project_id ?>">
			<td class="<?= task_state($task->status)?>"><a href="<?= $url.$id ?>/view"><?= $task->name ?></a></td>
			<td>
				<?php if ($parent_id !== null && isset($tasks[$parent_id])) { ?>
				<a href="<?= $url.$parent_id ?>/view"><?= $tasks[$parent_id]->name ?></a>
				<?php } ?>
			</td>
			<td>
				<span class="hover_h">
				<a href="../project/<?= $task->project_id ?>/view"><?= $project['name'] ?></a>
				</span>
			</td>
			<td><?= t(task_state($task->status)) ?></td>
			<td><?= $task->start_date ?></td>
			<td><?= $task->due_date ?></td>
			<td>
				<a title="<?= t('edit')?>"     href="<?= $url.$id ?>/edit?redirect=../index"     class="symbol"></a>
				<a title="<?= t('complete')?>" href="<?= $url.$id ?>/complete?redirect=../index" class="<?= $task->status == TASK_STATUS_COMPLETE ? 'hidden':'symbol'?>"></a>
				<a title="<?= t('cancel')?>"   href="<?= $url.$id ?>/cancel?redirect=../index"   class="<?= $task->status == TASK_STATUS_CANCELED ? 'hidden':'symbol'?>"></a>
				<a title="<?= t('do open')?>"  href="<?= $url.$id ?>/open?redirect=../index"     class="<?= in_array($task->status,[TASK_STATUS_OPEN,TASK_STATUS_STARTED])? 'hidden':'symbol'?>"></a>
				<a title="<?= t('wait')?>"     href="<?= $url.$id ?>/wait?redirect=../index"	  	class="<?= $task->status == TASK_STATUS_PENDING  ? 'hidden':'symbol'?>"></a>
				<a title="<?= t('start')?>  "  href="<?= $url.$id ?>/start?redirect=../index"    class="<?= $task->status == TASK_STATUS_STARTED  ? 'hidden':'symbol'?>"></a>
			</td>
		</tr>
	<?php }; // foreach ?>
</table>
	<?php } // tasks found
} // key given