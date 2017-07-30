<?php $title = 'Umbrella Task Management';

include '../bootstrap.php';
include 'controller.php';
require_login();

$task_id = param('id');
if (!$task_id) error('No task id passed!');
if ($selected = post('timetrack')){
	assign_task($task_id,$selected);
	redirect($selected.'/view');
}
$tracks = get_open_tracks($user->id);
$count = count($tracks);
if ($count < 1) error('No open time track existing!');
if ($count == 1) {
	assign_task($task_id,key($tracks));
	redirect(key($tracks).'/view');
}

$task = request('task','json?id='.$task_id);

include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>
<form method="POST">
	<fieldset><legend>Add Task "<?= $task['name'] ?>" to Timetrack</legend>
		<fieldset><legend>Open timetracks</legend>
			<select name="timetrack">
				<option value="">== Select a timetrack ==</option>
			<?php foreach ($tracks as $id => $track){ ?>
				<option value="<?= $id ?>"><?= $track['subject'] ?></option>
			<?php } ?>
			</select>
		</fieldset>
		<input type="submit" />
	</fieldset>
</form>

<?php include '../common_templates/closure.php'; ?>
