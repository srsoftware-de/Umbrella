<?php $title = 'Umbrella Task Management';

include '../bootstrap.php';
include 'controller.php';
require_login('time');

$task_id = param('tid');
if (!$task_id) error('No task id passed!');
if ($selected = post('timetrack')){
	assign_task($task_id,$selected);
	redirect($selected.'/view');
}
$tracks = get_open_tracks($user->id);
$count = count($tracks);
if ($count < 1) error(t('No open time track existing!'));
if ($count == 1) {
	assign_task($task_id,key($tracks));
	redirect(key($tracks).'/view');
}

$task = request('task','json',['id'=>$task_id]);

include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>
<form method="POST">
	<fieldset><legend><?= t('Add Task "?" to Timetrack',$task['name']) ?></legend>
		<fieldset><legend><?= t('Open timetracks') ?></legend>
			<select name="timetrack">
				<option value=""><?= t('== Select a timetrack ==')?></option>
			<?php foreach ($tracks as $id => $track){ ?>
				<option value="<?= $id ?>"><?= $track['subject'] ?></option>
			<?php } ?>
			</select>
		</fieldset>
		<input type="submit" />
	</fieldset>
</form>

<?php include '../common_templates/closure.php'; ?>