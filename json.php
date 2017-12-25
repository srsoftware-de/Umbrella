<?php $title = 'Umbrella Notes Management';

include '../bootstrap.php';
include 'controller.php';

require_login('notes');

$url = param('url');
assert($url !== null,'Called notes/json without url');
$notes = Note::load(['url'=>$url]);
echo json_encode($notes);
