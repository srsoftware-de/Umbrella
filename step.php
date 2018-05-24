<?php $title = 'Umbrella Document Management';

include '../bootstrap.php';
include 'controller.php';

require_login('document');

$id = param('id');
assert(is_numeric($id),'No valid document id passed to edit!');
$document = Document::load(['ids'=>$id]);
if (!$document) error('No document found or accessible for id ?',$id);

$company_id = $document->company_id;

$new_doc = $document->derive(param('type'));
redirect('../'.$new_doc->id.'/view');
