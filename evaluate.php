<?php include 'controller.php';

require_login('poll');

$poll_id = param('id');
if (empty($poll_id)) {
	error('No poll id provided!');
	redirect(getUrl('poll'));
}

$poll = Poll::load(['ids'=>$poll_id]);
if (empty($poll)){
	error('You are not allowed to modify this poll!');
	redirect(getUrl('poll'));
}

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>


<?php include '../common_templates/closure.php';