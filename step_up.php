<?php include 'controller.php';

require_login('model');

$base_url = getUrl('model');

$step_id = param('id');
if (empty($step_id)){
	error('No step id passed to edit!');
	redirect($base_url);
}

$step = Step::load(['ids'=>$step_id]);
if (empty($step)){
	error('You are not allowed to access this step!');
	redirect($base_url);
}

$phase = $step->phase();
if (empty($phase)){
	error('You are not allowed to access this phase!');
	redirect($base_url);
}

$diagram = $phase->diagram();
if (empty($diagram)){
	error('You are not allowed to access this diagram!');
	redirect($base_url);
}

$step->moveUp();
redirect($base_url.'diagram/'.$diagram->id.'#step'.$step_id);