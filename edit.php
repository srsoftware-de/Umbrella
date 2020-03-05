<?php include 'controller.php';

require_login('poll');

$poll_id = param('id');
if (empty($poll_id)) {
	error('No poll id provided!');
	redirect(getUrl('poll'));
}

$poll = Poll::load(['ids'=>$poll_id]);

if (empty($poll->users($user->id)) || (($poll->users($user->id) & Poll::EDIT) == 0)){
	error('You are not allowed to modify poll ◊!',$poll_id);
	redirect(getUrl('poll'));
}

if (!empty(post('name'))){
	$poll->patch($_POST)->save();
	redirect(getUrl('poll',$poll_id.'/options'));
}

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>

<fieldset>
	<legend><?= t('edit poll "◊"',$poll->name)?></legend>
	<form method="POST">
		<fieldset>
			<legend><?= t('Name')?></legend>
			<input name="name" value="<?= $poll->name ?>" />
		</fieldset>
		<fieldset>
			<legend><?= t('Description')?></legend>
			<textarea name="description"><?= $poll->description ?></textarea>
		</fieldset>
		<button type="submit"><?= t('Submit')?></button>
	</form>
</fieldset>

<?php include '../common_templates/closure.php';
