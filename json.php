<?php include 'controller.php';

$user = User::require_login();

$messages = param('messages');
if ($messages) {
	$messages = Message::load(['user_id'=>$user->id,'state'=>Message::WAITING,'last_id'=>$messages]);
	$users = User::load(['target'=>'json']);
	foreach ($messages as $message) $message->from = $users[$message->author];
	die(json_encode($messages));
}

$options = ['target'=>'json'];
if ($ids = param('ids',param('id'))) $options['ids'] = $ids;
if (param('related') == true) $options['related'] = true; // only list users the current user shares a project or company with
$data = User::load($options);
if (empty($data)) {
	http_response_code(400);
	die(t('No such user'));
}
die(json_encode($data));
