<?php $title = 'Umbrella Timetracking';

include '../bootstrap.php';
include 'controller.php';

require_login('time');

$time = Timetrack::load(['ids'=>param(id)]);

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

if ($redirect = param('returnTo')){
	redirect($redirect);
} else redirect(getUrl('time',$this->id.'/view'));