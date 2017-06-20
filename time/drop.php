<?php $title = 'Umbrella Time Tracking';

include '../bootstrap.php';
include 'controller.php';
require_login();

$time_id = param('id');
assert(is_numeric($time_id),'No valid time id passed to drop!');
drop_time($time_id);
redirect('../index');
