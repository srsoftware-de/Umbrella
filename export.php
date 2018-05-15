<?php $title = 'Umbrella Timetracking';

include '../bootstrap.php';
include 'controller.php';

require_login('time');
$times = load_times(['order'=>param('order','state')]);
$task_ids = [];

$parsedown = null;
if (file_exists('../lib/parsedown/Parsedown.php')){
	include '../lib/parsedown/Parsedown.php';
	$parsedown = Parsedown::instance();
}

foreach ($times as &$time){
	foreach ($time['task_ids'] as $task_id) $task_ids[$task_id] = 1;
//	if ($parsedown) $time['description'] = $parsedown->parse($time['description']);	
}
unset($time);
$tasks = request('task','json',['ids'=>array_keys($task_ids)]);
$project_ids = [];
foreach ($tasks as $task) $project_ids[$task['project_id']] = 1;
$projects = request('project','json',['ids'=>array_keys($project_ids)]);

$show_complete = param('complete') == 'show';

header('Content-Type: application/csv');
// tell the browser we want to save it instead of displaying it
header('Content-Disposition: attachment; filename="timetrack.csv";');
?>
"<?= t('Projects')?>";"<?= t('Subject')?>";"<?= t('Description')?>";"<?= t('Start')?>";"<?= t('End')?>";"<?= t('Hours')?>";"<?= t('State')?>"
<?php foreach ($times as $id => $time){ ?>"<?php  
	if (!$show_complete && $time['state'] == TIME_STATUS_COMPLETE) continue;
	$time_projects=[];
	foreach ($time['task_ids'] as $task_id){
		$pid = $tasks[$task_id]['project_id'];
		$time_projects[$pid] = $projects[$pid]['name'];
	}
	foreach ($time_projects as $pid => $name){ echo $name.' '; } 
?>";"<?= html2plain($time['subject']) ?>";"<?= html2plain($time['description']) ?>";"<?= $time['start_time']?date('Y-m-d H:i',$time['start_time']):''; ?>";"<?= $time['end_time']?date('Y-m-d H:i',$time['end_time']):'<a href="'.$id.'/stop">Stop</a>'; ?>";"<?= $time['end_time']?str_replace('.',',',round(($time['end_time']-$time['start_time'])/3600,2)):'' ?>";"<?= t(state_Text($time['state'])) ?>"
<?php } ?>