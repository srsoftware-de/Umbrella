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
$users = request('user','json',['related'=>true]);

if ($permissions = post('permission')){
	$poll->share($permissions);
	redirect(getUrl('poll'));
}

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>

<fieldset>
	<legend><?= t('share poll "◊"',$poll->name)?></legend>
	<form method="POST">
		<table>
			<tr>
				<th><?= t('Users')?></th>
				<th><?= t('Participate') ?></th>
				<th><?= t('Evaluate') ?></th>
				<th><?= t('Edit') ?></th>
			</tr>
		<?php foreach ($users as $uid => $u) {
			if ($uid == $user->id) continue; ?>
			<tr>
				<td><?= $u['login'] ?></td>
				<td><input type="radio" name="permission[<?= $u['id']?>]" value="<?= Poll::PARTICIPATE?>" <?= empty($poll->users($uid)) ? 'checked="checked" ':' ' ?>/></td>
				<td><input type="radio" name="permission[<?= $u['id']?>]" value="<?= Poll::EVALUATE?>" <?= $poll->users($uid) == Poll::EVALUATE ? 'checked="checked" ':' ' ?>/></td>
				<td><input type="radio" name="permission[<?= $u['id']?>]" value="<?= Poll::EDIT?>" <?= $poll->users($uid) == Poll::EDIT ? 'checked="checked" ':' ' ?> /></td>
			</tr>
		<?php } ?>
		</table>
		<button type="submit"><?= t('Submit')?></button>
	</form>
</fieldset>

<?php include '../common_templates/closure.php';
