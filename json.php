<?php include 'controller.php';

require_login('notes');

$uri = param('uri');
if ($uri == null) throw new Exception('Called notes/json without uri');
$notes = Note::load(['uri'=>$uri]);
echo json_encode($notes);
