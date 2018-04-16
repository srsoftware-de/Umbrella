<?php $title = 'Umbrella Model Management';

include '../bootstrap.php';
include 'controller.php';

require_login('model');

$model_id = param('id1');
$process_id = param('id2');

if (!$model_id){
	error('No model id passed to terminal.');
	redirect(getUrl('model'));
}
if (!$process_id){
	error('No process id passed to terminal.');
	redirect(getUrl('model'));
}

$process = Process::load(['ids'=>$process_id]);
$process->patch($_POST);
$process->save();
