<?php include 'controller.php';

require_login('rtc');

if ($id = param('id')){
	Channel::load(['ids'=>$id])->open();
} else redirect(getUrl('rtc'));