<?php $title = 'Umbrella Time Tracking';

include '../bootstrap.php';
include 'controller.php';
require_login('time');

start_time($user->id);
redirect('index');
