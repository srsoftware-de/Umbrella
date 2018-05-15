<?php $title = 'Umbrella Time Tracking';

include '../bootstrap.php';
include 'controller.php';
require_login('time');

$time_id = param('id');
set_state($time_id,TIME_STATUS_COMPLETE);
redirect('..');