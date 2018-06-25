<?php $title = 'Umbrella Timetracking';

include '../bootstrap.php';
include 'controller.php';

require_login('time');
$times = load_times(['order'=>param('order')]);
$task_ids = [];

$parsedown = null;
if (file_exists('../lib/parsedown/Parsedown.php')){
	include '../lib/parsedown/Parsedown.php';
	$parsedown = Parsedown::instance();
}

foreach ($times as &$time){
	foreach ($time['task_ids'] as $task_id) $task_ids[$task_id] = 1;
	if ($parsedown) $time['description'] = $parsedown->parse($time['description']);	
}
unset($time);
$tasks = request('task','json',['ids'=>array_keys($task_ids)]);
$project_ids = [];
foreach ($tasks as $task) $project_ids[$task['project_id']] = 1;
$projects = request('project','json',['ids'=>array_keys($project_ids)]);

$show_complete = param('complete') == 'show';


include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php';

if ($show_complete){ ?>
<a class="symbol" title="<?= t('export times')?>" href="export?complete=show"></a>
<?php } else { ?> 
<a class="symbol" title="<?= t('show completed times') ?>" href="?complete=show"></a>
<a class="symbol" title="<?= t('export times to CSV')?>" href="export"></a>
<?php } ?>


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
	if (!$show_complete && $time['state'] == TIME_STATUS_COMPLETE) continue;
	$time_projects=[];
	foreach ($time['task_ids'] as $task_id){
		$pid = $tasks[$task_id]['project_id'];
		$time_projects[$pid] = $projects[$pid]['name'];
	} ?>
	<tr class="project<?= implode(' project',array_keys($time_projects))?>">
		<td>
		<?php foreach ($time_projects as $pid => $name){?>
			<span class="hover_h">
				<a href="<?= getUrl('project',$pid.'/view') ?>"><?= $name ?></a>&nbsp;<a href="#" class="symbol" onclick="toggle('tr:not(.project<?= $pid ?>)')"></a>
			</span>
		<?php }?>
		</td>
		<td><a href="<?= $id ?>/view"><?= $time['subject'] ?></a></td>
		<td><a href="<?= $id ?>/view"><?= $time['description'] ?></a></td>
		<td><a href="<?= $id ?>/view"><?= $time['start_time']?date('Y-m-d H:i',$time['start_time']):''; ?></a></td>
		<td><a href="<?= $id ?>/view"><?= $time['end_time']?date('Y-m-d H:i',$time['end_time']):'<a href="'.$id.'/stop">Stop</a>'; ?></a></td>
		<td><a href="<?= $id ?>/view"><?= $time['end_time']?round(($time['end_time']-$time['start_time'])/3600,2):'' ?></a></td>
		<td><a href="<?= $id ?>/edit"><?= t(state_text($time['state'])) ?></a></td>
		<td>
			<?php if ($time['end_time']) { ?>
			<a class="symbol" title="<?= t('edit') ?>" href="<?= $id ?>/edit"></a>
			<?php } ?>
			<a class="symbol" title="<?= t('drop') ?>" href="<?= $id ?>/drop"></a>
			<a class="symbol" title="<?= t('complete') ?>" href="<?= $id ?>/complete"></a>
		</td>
	</tr>
<?php } ?>

</table>
<?php include '../common_templates/closure.php'; ?>
