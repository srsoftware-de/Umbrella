<?php include 'controller.php';

$user = User::require_login();
$users = User::load();

$receivers = post('receivers');
if (empty($receivers)) error('No receiver(s) selected');

$content = post('content');

if (!empty($content)){
	$subject = post('subject');
	if (empty($subject)) error('No subject set');

	if (no_error()) {
		$response = request('user','notify',['subject'=>$subject,'body'=>$content,'recipients'=>array_keys($receivers)],true,NO_CONVERSION);
		debug(['response'=>$response],1);
	}
}


include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>
<p>
You are <?= $user->login ?>
</p>
<form method="POST">
	<label>
		<input type="text" name="subject" />
		Subject
	</label>
	<label>
		<input type="text" name="content" />
		Content
	</label>
	<?php foreach ($users as $user) { ?>
	<br/>
	<label>
		<input type="checkbox" name="receivers[<?= $user->id ?>]" /> <?= $user->login ?>
	</label>
	<?php } // foreach user?>
	<p>
	<button type="submit">send</button>
	</p>
</form>

<?php include '../common_templates/closure.php';