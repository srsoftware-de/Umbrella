<?php include 'controller.php';

$user = User::require_login();

$subject = post('subject'); // text
if (empty($subject)) error('No subject passed to notify!');

$body = post('body'); // text
if (empty($body)) error('No message body passed to notify!');

$recipients = post('recipients'); // user id(s)
if (empty($recipients)) error('No recipient(s) passed to notify!');

if (no_error()) {
	if (!is_array($recipients)) $recipients = [ $recipients ];
	$users = User::load(['ids'=>$recipients]);

	$message = new Message();
	$message->patch(['author'=>$user->id,'timestamp'=>time(),'subject'=>$subject,'body'=>$body])->assignTo($users);

}
Message::delivery();