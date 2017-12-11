<?php $title = 'Umbrella Timetracking';

include '../bootstrap.php';
include 'controller.php';

require_login('time');

if ($open = param('OPEN')){
	$open = explode(',',$open);
	foreach ($open as $time_id){
		set_state($time_id, TIME_STATUS_OPEN);
	}
}
if ($pending = param('PENDING')){
	$pending = explode(',',$pending);
	foreach ($pending as $time_id){
		set_state($time_id, TIME_STATUS_PENDING);
	}
}
if ($complete = param('COMPLETED')){
	$complete = explode(',',$complete);
	foreach ($complete as $time_id){
		set_state($time_id, TIME_STATUS_COMPLETE);
	}
}
