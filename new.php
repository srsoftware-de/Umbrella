<?php include 'controller.php';

require_login('rtc');
$subject = param('subject',t('new conversation'));
if ($selected_users = param('users')){
	$channel = new Channel();
	$channel->patch(['subject'=>$subject,'users'=>$selected_users]);
	$channel->save();
	redirect(getUrl('rtc','open/'.$channel->id));
}

$users_raw = request('user','json');
$users = [];

foreach ($users_raw as $uid => $u) $users[$uid] = $u['login'];
asort($users,SORT_REGULAR|SORT_FLAG_CASE);

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php'; 
include '../common_templates/messages.php'; ?>

<form method="POST">
	<fieldset>
		<legend><?= t('Subject')?></legend>
		<input type="text" name="subject" value="<?= $subject ?>"/>
	</fieldset>
	<fieldset>
		<legend><?= t('Users')?></legend>
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