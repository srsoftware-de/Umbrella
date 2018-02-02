<?php $title = 'Umbrella Document Management';

include '../bootstrap.php';
include 'controller.php';

require_login('document');

$document_id = param('id');
$position = param('pos');

$document = reset(Document::load(['ids'=>$document_id]));
$document->elevate($position);
redirect('view');
