<?php include 'controller.php';

require_login('model');

$process_id = param('id');
if (empty($process_id)) throw new Exception('No process id passed!');

$term_id = param('elem');
if (empty($term_id)) throw new Exception('No terminal id passed!');

$terminal = TerminalInstance::load($process_id,$term_id);
$terminal->patch($_POST)->save();