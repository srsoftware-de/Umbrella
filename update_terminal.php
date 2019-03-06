<?php include 'controller.php';

require_login('model');

$terminal_id = param('id');
if (empty($terminal_id)) throw new Exception('No terminal id passed!');

$place_id = param('place_id');
if (!empty($place_id)) {
	Terminal::updatePlace($_POST);
} else Terminal::load(['ids'=>$terminal_id])->patch($_POST)->save();