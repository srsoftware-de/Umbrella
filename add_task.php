<?php $title = 'Umbrella Task Management';

include '../bootstrap.php';
include 'controller.php';
require_login('time');

$task_id = param('tid');
if (!$task_id) error('No task id passed!');

$task = request('task','json',['ids'=>$task_id]);
$tracks = get_open_tracks($user->id);

$selected = post('timetrack');

if (count($tracks)<1) $selected = start_time($user->id);

if ($selected !== null){
	assign_task($task,$selected);
	redirect($selected.'/view');
}

include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>
<form method="POST">
	<fieldset><legend><?= t('Add Task "?" to Timetrack',$task['name']) ?></legend>
		<fieldset><legend><?= t('Open timetracks') ?></legend>
			<select name="timetrack">
				<?php foreach ($tracks as $id => $track){ ?>
				<option value="<?= $id ?>"><?= $track['subject'] ?></option>
				<?php } ?>
				<option value="0"><?= t('Start new time track')?></option>				
			</select>
		</fieldset>
		<input type="submit" />
	</fieldset>
</form>

<?php include '../common_templates/closure.php'; ?>