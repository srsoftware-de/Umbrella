<?php include 'controller.php';

/** @var mixed $dummy used in loop */

require_login('time');

$options = ['order'=>param('order','state')];
if ($project_id = param('project')) $options['project_id'] = [$project_id];
$times = Timetrack::load($options);
$show_complete = param('complete') == 'show';

header('Content-Type: application/csv');
// tell the browser we want to save it instead of displaying it
header('Content-Disposition: attachment; filename="timetrack.csv";');
?>
"<?= t('Projects')?>";"<?= t('Subject')?>";"<?= t('Description')?>";"<?= t('Start')?>";"<?= t('End')?>";"<?= t('Hours')?>";"<?= t('State')?>"
<?php foreach ($times as $id => $time){
	if (!$show_complete && $time->state == TIME_STATUS_COMPLETE) continue; ?>"<?php
	$projects = [];
	foreach ($time->tasks as $dummy => $task) $projects[$task['project']['name']]=true;
	echo implode(', ', array_keys($projects));
?>";"<?= html2plain($time->subject) ?>";"<?= html2plain($time->description) ?>";"<?= $time->start_time?date('Y-m-d H:i',$time->start_time):''; ?>";"<?= $time->end_time?date('Y-m-d H:i',$time->end_time):'<a href="'.$id.'/stop">Stop</a>'; ?>";"<?= $time->end_time?str_replace('.',',',round(($time->end_time-$time->start_time)/3600,2)):'' ?>";"<?= t($time->state()) ?>"
<?php } ?>