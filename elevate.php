<?php $title = 'Umbrella Invoice Management';

include '../bootstrap.php';
include 'controller.php';

require_login('invoice');

$invoice_id = param('id');
$position = param('pos');

$invoice = reset(Invoice::load($invoice_id));
$invoice->elevate($position);
redirect('view');
