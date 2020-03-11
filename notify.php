<?php include 'controller.php';

/*==================================================================================
This file processes messages to users:

if an hour parameter is passed, pending messages are delivered to all
users that have the corresponding time set in their preferences
and to all users that have instant delivery activated

if, instead, a message is passed, this message is only delivered to
users who wish instant delivery. for all other users this message
is sheduled for delivery at the next matching time

for delivery of aggregated messages, this file should be enabled via cron:
0  8 * * * wget https://<domain>/user/notify?hour=8
0 10 * * * wget https://<domain>/user/notify?hour=10
0 12 * * * wget https://<domain>/user/notify?hour=12
0 14 * * * wget https://<domain>/user/notify?hour=14
0 16 * * * wget https://<domain>/user/notify?hour=16
0 18 * * * wget https://<domain>/user/notify?hour=18
0 20 * * * wget https://<domain>/user/notify?hour=20


Here is how to call this from php code:
 $message = ['recipients'=>$recipients,'subject'=>$subject,'body'=>$body,'meta'=>$meta];
 request('user','notify',$message);
 $recipients is an array of user ids
 $meta is an array of metadata, like
/*==================================================================================*/




// if an hour parameter is passed:
// deliver pending messages
$hour = param('hour');
if ($hour !== null){
	Message::deliver($hour);
	die();
}

// if no hour parameter is passed:
//try to schedule/send new message – requires user to be logged in
$user = User::require_login();

$subject = post('subject'); // string
if (empty($subject)) error('No message body passed!');

$body = post('body'); // string
if (empty($body)) error('No message body passed!');

$recipients = post('recipients'); // array of user ids
if (empty($recipients)) error('No recipient(s) passed!');

$meta = post('meta'); // json string
if ($meta !== null) $meta = json_decode($meta);

if (no_error()){
	if (!is_array($recipients)) $recipients = [ $recipients ];
	$users = User::load(['ids'=>$recipients]);

	$message = new Message();
	$message->patch([
			'author'=>$user->id,
			'timestamp'=>time(),
			'subject'=>$subject,
			'body'=>$body,
			'meta'=>$meta
	])->assignTo($users);
	Message::deliver();
}
