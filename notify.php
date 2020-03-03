<?php include 'controller.php';

/*==================================================================================
This file processes messages to users:

if a time parameter is passed, pending messages are delivered to all
users that have the corresponding time set in their preferences
and to all users that have instant delivery activated

if, instead, a message is passed, this message is only delivered to
users who wish instant delivery. for all other users this message
is sheduled for delivery at the next matching time

for delivery of aggregated messages, this file should be enabled via cron:
0  8 * * * wget https://<domain>/user/notify?time=1
0 10 * * * wget https://<domain>/user/notify?time=2
0 12 * * * wget https://<domain>/user/notify?time=4
0 14 * * * wget https://<domain>/user/notify?time=8
0 16 * * * wget https://<domain>/user/notify?time=16
0 18 * * * wget https://<domain>/user/notify?time=32
0 20 * * * wget https://<domain>/user/notify?time=64
/*==================================================================================*/


// here is how to call this from php code:
// $message = ['recipients'=>$recipients,'subject'=>$subject,'body'=>$body];
// request('user','notify',$message);

// process aggregated messages at specific time, does not require user login
$time = param('time');
if (!empty($time)){
	Message::delivery($time);
	die();
}

// shedule/process message from specific user
$user = User::require_login();

$subject = post('subject'); // text
if (empty($subject)) error('No subject passed to notify!');

$body = post('body'); // text
if (empty($body)) error('No message body passed to notify!');

$recipients = post('recipients'); // user id(s)
if (empty($recipients)) error('No recipient(s) passed to notify!');

$meta = post('meta',null);
if ($meta !== null) $meta = json_encode($meta);
if (no_error()) {
	if (!is_array($recipients)) $recipients = [ $recipients ];
	$users = User::load(['ids'=>$recipients]);

	$message = new Message();
	$message->patch(['author'=>$user->id,'timestamp'=>time(),'subject'=>$subject,'body'=>$body,'meta'=>$meta])->assignTo($users);
	Message::delivery();
}
