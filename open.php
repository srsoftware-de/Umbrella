<?php include 'controller.php';

require_login('rtc');

$channel = null;
if ($hash = param('id')) $channel = Channel::load(['hashes'=>$hash]);
if ($users = param('users')) {
	if (!is_array($users)) $users = explode(',',$users); 
	$channel = Channel::load(['users'=>$users]);
	if (empty($channel)) $channel = (new Channel())->patch(['users'=>$users]); // create new channel for users
}
if ($channel) $channel->open();
redirect(getUrl('rtc'));