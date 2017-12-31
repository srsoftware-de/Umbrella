<?php $title = 'Umbrella Timetracking';

include '../bootstrap.php';
include 'controller.php';

require_login('time');
$times = load_times(['order'=>param('order')]);
$task_ids = [];
foreach ($times as $time){
	foreach ($time['task_ids'] as $task_id) $task_ids[$task_id] = 1;
}
$tasks = request('task','json',['ids'=>implode(',', array_keys($task_ids))]);
$project_ids = [];
foreach ($tasks as $task) $project_ids[$task['project_id']] = 1;
$projects = request('project','json',['ids'=>implode(',',array_keys($project_ids))]);

include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<table>
	<tr>
		<th><?= t('Projects')?></th>
		<th><a href="?order=subject"><?= t('Subject')?></a></th>
		<th><a href="?order=description"><?= t('Description')?></a></th>
		<th><a href="?order=start_time"><?= t('Start')?></a></th>
		<th><a href="?order=end_time"><?= t('End')?></a></th>
		<th><?= t('Hours')?></th>
		<th><a href="?order=state"><?= t('State')?></a></th>
		<th><?= t('Actions')?></th>
	</tr>
	
<?php foreach ($times as $id => $time){ 
	if ($time['state'] == TIME_STATUS_COMPLETE) continue;
?>
	<tr>
		<td>
			<?php $time_projects=[]; 
			foreach ($time['task_ids'] as $task_id){
				$pid = $tasks[$task_id]['project_id'];
				$time_projects[$pid] = $projects[$pid]['name'];
			}?>
			<?= implode(' ',$time_projects)?>
		</td>
		<td><a href="<?= $id ?>/view"><?= $time['subject'] ?></a></td>
		<td><?= $time['description'] ?></td>
		<td><?= $time['start_time']?date('Y-m-d H:i',$time['start_time']):''; ?></td>
		<td><?= $time['end_time']?date('Y-m-d H:i',$time['end_time']):'<a href="'.$id.'/stop">Stop</a>'; ?></td>
		<td><?= $time['end_time']?t('? hours',($time['end_time']-$time['start_time'])/3600):'' ?></td>
		<td><?= t(TIME_STATES[$time['state']]) ?></td>
		<td>
			<?php if ($time['end_time']) { ?>
			<a class="symbol" title="edit" href="<?= $id ?>/edit"></a>
			<?php } ?>
			<a class="symbol" title="drop" href="<?= $id ?>/drop">	</a>
		</td>
	</tr>
<?php } ?>

</table>
<?php include '../common_templates/closure.php'; ?>
