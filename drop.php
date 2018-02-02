<?php $title = 'Umbrella Document Management';

include '../bootstrap.php';
include 'controller.php';

require_login('document');

$document_id = param('id');
$index = param('pos');

$document = reset(Document::load(['ids'=>$document_id]));
$positions = $document->positions();
if (isset($positions[$index])) $positions[$index]->delete();
redirect('view');
