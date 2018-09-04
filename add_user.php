<?php include 'controller.php';

require_login('rtc');

if ($hash = param('id')){
	$channel = Channel::load(['hashes'=>$hash]);
	if ($users = param('users')) $channel->addUsers($users)->open();
} else {
	warn('No channel hash given!');
	redirect(getUrl('rtc'));
}

$users = [];
$users_raw = request('user','json');
foreach ($users_raw as $uid => $u) $users[$uid] = $u['login'];
asort($users,SORT_REGULAR|SORT_FLAG_CASE);



include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<form method="POST">
	<fieldset>
		<legend><?= t('Add users to "?"',$channel->hash)?></legend>
		<?php foreach ($users as $uid => $login){
			if (in_array($uid, $channel->users)) continue; ?>
			<label>
				<input type="checkbox" name="users[]" value="<?= $uid ?>">
				<?= $login ?>
			</label><br/>
		<?php }?>
	</fieldset>
	<input type="submit" />
	
</form>

<?php include '../common_templates/closure.php'; ?>