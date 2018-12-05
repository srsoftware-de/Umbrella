<?php include 'controller.php';

require_login('rtc');

$subject = param('subject',t('new conversation'));
if ($selected_users = param('users')) (new Channel())->patch(['users'=>$selected_users])->save()->open();

$users_raw = request('user','json');
$users = [];

if (count($users_raw)<2) warn('In order to communicate with other users, you will have to create more users first. Go to the user module.');

foreach ($users_raw as $uid => $u) $users[$uid] = $u['login'];
asort($users,SORT_REGULAR|SORT_FLAG_CASE);

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<form method="POST">
	<fieldset>
		<legend><?= t('Select users to invite to conversation')?></legend>
		<?php foreach ($users as $uid => $login){
			if ($uid == $user->id) continue; ?>
			<label>
				<input type="checkbox" name="users[]" value="<?= $uid ?>">
				<?= $login ?>
			</label><br/>
		<?php }?>
	</fieldset>
	<input type="submit" />

</form>

<?php include '../common_templates/closure.php'; ?>