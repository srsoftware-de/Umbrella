<?php
include 'controller.php';
include '../bootstrap.php';

require_login('rtc');

if ($text = param('message')){
	$message = new Message($text);
	$message->save();
	die(''.$message->time);
}
if ($time = param('time')){
	$message = Message::load(['from'=>$time]);
	die(json_encode($message));
}
die(''.Message::time());