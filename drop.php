<?php $title = 'Umbrella Invoice Management';

include '../bootstrap.php';
include 'controller.php';

require_login('invoice');

$invoice_id = param('id');
$index = param('pos');

$invoice = reset(Invoice::load($invoice_id));
$invoice->remove_position($index);
redirect('edit');