<?php include 'controller.php';

require_login('model');

$process_id = param('id');
if (empty($process_id)) throw new Exception('No process id passed!');

$child_id = param('elem');
if (empty($child_id) || $child_id == $process_id){
	$process = Process::load(['ids'=>$process_id]);
} else $process = ProcessChild::load($process_id,$child_id);
$process->patch($_POST)->save();