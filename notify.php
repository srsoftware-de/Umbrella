<?php include 'controller.php';

$user = User::require_login();

$subject = post('subject'); // text
if (empty($subject)) error('No subject passed to notify!');

$body = post('body'); // text
if (empty($body)) error('No message body passed to notify!');

$recipients = post('recipients'); // user id
if (empty($recipients)) error('No recipient(s) passed to notify!');

if (no_error()) {
	if (!is_array($recipients)) $recipients = [ $recipients ];
	$users = User::load(['ids'=>$recipients]);

	$message = new Message();
	$message->patch(['author'=>$user->id,'timestamp'=>time(),'subject'=>$subject,'body'=>$body])->deliverTo($users);
}

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php';

if (!empty($message)) debug($message);
?>

<form method="POST">
	Subject: <input type="text" name="subject" value="" /><br/>
	Message: <input type="text" name="body" value="" /><br/>
	Recipients: <div>
		<input type="checkbox" name="recipients[]" value="2" checked="checked"/>
	</div>
	<input type="submit"/>
</form>

<?php

include '../common_templates/closure.php';