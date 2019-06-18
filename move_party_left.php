<?php include 'controller.php';

require_login('model');

$base_url = getUrl('model');

$party_id = param('id');
if (empty($party_id)){
	error('No party id passed!');
	redirect($base_url);
}

$party = Party::load(['ids'=>$party_id]);
if (empty($party)){
	error('You are not allowed to access this party!');
	redirect($base_url);
}

$diagram = $party->diagram();
if (empty($diagram)){
	error('You are not allowed to access this diagram!');
	redirect($base_url);
}

$left_party = Party::load(['diagram_id'=>$diagram->id,'position'=>$party->position-1]);
$left_party->patch(['position'=>$party->position])->save();
$party->patch(['position'=>$party->position-1])->save();

redirect($base_url.'diagram/'.$diagram->id);