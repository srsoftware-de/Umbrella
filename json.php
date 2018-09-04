<?php include 'controller.php';

require_login('rtc');

$options = [];
if ($users = param('users')) $options['users'] = $users;

$channels = Channel::load($options);

die(json_encode($channels));