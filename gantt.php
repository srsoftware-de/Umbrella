<?php include 'controller.php';

require_login('project');

if ($pid = param('id')){
	$tasks = request('task','json',['project_ids'=>$pid,'order'=>'start_date']);

	$plotted = [];
	$pending = [];
	$min = null;
	$max = null;
	$count = 0;
// Pseudo-Code: siehe https://umbrella.keawe.de/files/?path=project/3/Projects/Gantt-Diagramme
	foreach ($tasks as $tid => $task){
		if ($task['status'] > TASK_STATUS_PENDING) continue;
		$start = hour($task['start_date']);
		$task['start'] = $start;
		$due   = hour($task['due_date']);
		$task['due'] = $due;
		
		if ($start && ($min === null || $start < $min)) $min = $start;
		if ($start && ($max === null || $start > $max)) $max = $start;
		if ($due && ($min === null || $due < $min)) $min = $due;
		if ($due && ($max === null || $due > $max)) $max = $due;
		
		$count++;
		
		$plotted[$tid] = $task;
		//debug($task,1);
	} 
} else {
	error('No project id set!');
}

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php';

?>
<p>
<a href="https://umbrella.keawe.de/files/?path=project/3/Projects/Gantt-Diagramme">&rarr; Pseudo-Code / Struktogramm</a>
</p>

<fieldset class="scrolling">
<svg id="gantt" viewBox="0 0 <?= $max-$min+20 ?> <?= (40*$count)?>" style="width: <?= $max-$min ?>px; height: <?= 40*$count ?>px">
	<?php $y=5; $url = getUrl('task');
	foreach ($plotted as $task) {
		?><rect x="0" y="<?= $y ?>" width="<?= $max-$min+10 ?>" height="30" class="row" onclick="location.href='<?= $url.$task['id'].'/view' ?>'"><title><?= $task['name']?></title></rect><?php
		if ($task['start']){
			if ($task['due']){
				?><rect x="<?= $task['start']-$min ?>" y="<?= $y?>" width="<?= $task['due']-$task['start'] ?>" height="30" class="schedule" /><?php
				if ($task['est_time']>0) {
					?><rect x="<?= $task['start']-$min ?>" y="<?= $y?>" width="<?= 3*$task['est_time'] ?>" height="30" class="duration" /><?php
				}
			} else {
				?><rect x="<?= $task['start']-$min ?>" y="<?= $y?>" width="10" height="30" class="schedule start" /><?php
				if ($task['est_time']>0) {
					?><rect x="<?= $task['start']-$min ?>" y="<?= $y?>" width="<?= 3*$task['est_time'] ?>" height="30" class="duration" /><?php
				}
			}
		} else {
			if ($task['due']){
				?><rect x="<?= $task['due']-$min-10 ?>" y="<?= $y?>" width="10" height="30" class="schedule stop" /><?php
				if ($task['est_time']>0) {
					?><rect x="<?= $task['due']-$task['est_time']-$min ?>" y="<?= $y?>" width="<?= 3*$task['est_time'] ?>" height="30" class="duration" /><?php
				}
			} else {
			} 
		}
		?><text id="tx_<?= $y ?>" x="5" y="<?= $y+20 ?>"><?= $task['name']?></text><?php
				
		$y+=40;
	} ?>
</svg>
</fieldset>
<?php include '../common_templates/closure.php'; ?>