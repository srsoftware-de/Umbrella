<?php $title = 'Umbrella Invoice Management';

include '../bootstrap.php';
include 'controller.php';

require_login('invoice');

$id = param('id');
assert(is_numeric($id),'No valid invoice id passed to edit!');
$invoice = reset(Invoice::load(['ids'=>$id]));
if (!$invoice) error('No invoice found or accessible for id ?',$id);

$company_id = $invoice->company_id;

$new_doc = $invoice->derive();
redirect('../'.$new_doc->id.'/view');
