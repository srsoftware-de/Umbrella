<?php include 'controller.php';

require_login('document');

$document_id = param('id');
$position = param('pos');

$document = Document::load(['ids'=>$document_id]);
$document->elevate($position);
redirect('view');
