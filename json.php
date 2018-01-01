<?php $title = 'Umbrella Notes Management';

include '../bootstrap.php';
include 'controller.php';

require_login('notes');

$uri = param('uri');
assert($uri !== null,'Called notes/json without uri');
$notes = Note::load(['uri'=>$uri]);
echo json_encode($notes);
