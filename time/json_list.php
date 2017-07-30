<?php

include '../bootstrap.php';
include 'controller.php';

	require_login();
	
	$times = get_time_list();
	$assignments = get_time_assignments(array_keys($times));
	foreach($times as $time_id => &$time){
		$time['tasks'] = $assignments[$time_id];
	}	
	echo json_encode($times);
?>