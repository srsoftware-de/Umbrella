<?php include 'controller.php';

require_login('poll');

$poll_id = param('id');
if (empty($poll_id)) {
	error('No poll id provided!');
	redirect(getUrl('poll'));
}

$poll = Poll::load(['ids'=>$poll_id]);
if (empty($poll)){
	error('You are not allowed to modify this poll!');
	redirect(getUrl('poll'));
}

$option_id = param('option');
if (empty($option_id)){
	error('No option id passed!');
	redirect(getUrl('poll','options?id='.$poll_id));
}

if (!array_key_exists($option_id, $poll->options())){
	error('No such option!');
	redirect(getUrl('poll','options?id='.$poll_id));
}

$option = $poll->options()[$option_id];

$name = post('name');
if (!empty($name)){
	$option->patch($_POST);
	$option->save();
	redirect(getUrl('poll','options?id='.$poll_id));
}

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>

<fieldset>
	<legend><?= t('edit option "◊"',$option->name)?></legend>
	<form method="POST">
		<fieldset>
			<legend><?= t('Name')?></legend>
			<input type="text" name="name" value="<?= $option->name ?>" />
		</fieldset>
		<fieldset>
			<legend><?= t('Description')?></legend>
			<textarea name="description"><?= $option->description ?></textarea>
		</fieldset>
		<button type="submit"><?= t('Submit')?></button>
	</form>
</fieldset>

<?php include '../common_templates/closure.php';?>