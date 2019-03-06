<?php include 'controller.php';

require_login('model');

$process_id = param('id');
if (empty($process_id)) throw new Exception('No process id passed!');

$place_id = param('place_id');
if (!empty($place_id)) {
	Process::updatePlace($_POST);
} else Process::load(['ids'=>$process_id])->patch($_POST)->save();