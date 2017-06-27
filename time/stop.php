<?php $title = 'Umbrella Timetracking';

include '../bootstrap.php';
include 'controller.php';

$time_id = param('id');
if (!$time_id) error('No time id passed to view!');


if ($subject = post('subject')){
	update_time($time_id,$subject,post('description'),post('start'),post('end'));
    redirect('..');
}

$time = load_time($time_id);
include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>
<form method="POST">
	<fieldset>
		<legend>Edit Time</legend>
		<fieldset>
			<legend>Subject</legend>
			<input type="text" name="subject" value="<?= $time['subject']; ?>"/>
		</fieldset>
		<fieldset>
			<legend>Description</legend>
			<textarea name="description"><?= $time['description']?></textarea>
		</fieldset>
		<fieldset>
			<legend>Start</legend>
			<input type="text" name="start" value="<?= date('Y-m-d H:i',$time['start_time']?$time['start_time']:time());?>" />
		</fieldset>
		<fieldset>
			<legend>End</legend>
			<input type="text" name="end" value="<?= date('Y-m-d H:i',$time['end_time']?$time['end_time']:time());?>" />
		</fieldset>
		<input type="submit" />
	</fieldset>
</form>

<?php include '../common_templates/closure.php'; ?>
