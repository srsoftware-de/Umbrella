<?php include 'controller.php';
require_login('time');

if ($time_id = param('id')){
	$times = Timetrack::load(['ids'=>$time_id]);
	$single = !is_array($times);
	if ($single) $times = [$times];
	
	foreach ($times as $time){
		if ($new_state = param('state')){
			switch ($new_state){
				case 'open':
					$time->patch(['state'=>TIME_STATUS_OPEN]); break;
				case 'pending':
					$time->patch(['state'=>TIME_STATUS_PENDING]); break;
				case 'complete':
					$time->patch(['state'=>TIME_STATUS_COMPLETE]); break;
			}
			$time->save();
		}
	}
	$redirect = getUrl('time',$single ? $time_id.DS.'view':'');
}

if ($pending_time_ids = param('PENDING')){ // used by document/view
	$times = Timetrack::load(['ids'=>explode(',',$pending_time_ids)]);
	foreach ($times as $time) $time->patch(['state'=>TIME_STATUS_PENDING])->save();
	$redirect = getUrl('time');
}

redirect(param('returnTo',$redirect));

