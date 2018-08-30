<?php include 'controller.php';

require_login('time');

$task_id = param('tid');
if (!$task_id) error('No task id passed!');

$task = request('task','json',['ids'=>$task_id]);
$times = Timetrack::load(['open'=>true]);

$selected = null;
if (count($times)<1) {
	$selected = Timetrack::startNew();
} else {
	$sel_idx = post('timetrack');
	switch ($sel_idx){
		case null:
			break;
		case 0:
			$selected = Timetrack::startNew();
			break;
		default:
			$selected = $times[$sel_idx];
	}
}

if ($selected !== null){
	$selected->assign_task($task)->save();
	redirect($selected->id.'/view');
}

include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>
<form method="POST">
	<fieldset><legend><?= t('Add Task "?" to Timetrack',$task['name']) ?></legend>
		<fieldset><legend><?= t('Open timetracks') ?></legend>
			<select name="timetrack">
				<?php foreach ($times as $id => $track){ ?>
				<option value="<?= $id ?>"><?= $track->subject ?></option>
				<?php } ?>
				<option value="0"><?= t('Start new time track')?></option>				
			</select>
		</fieldset>
		<input type="submit" />
	</fieldset>
</form>

<?php include '../common_templates/closure.php'; ?>