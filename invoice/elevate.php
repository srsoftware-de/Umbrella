<?php $title = 'Umbrella Invoice Management';

include '../bootstrap.php';
include 'controller.php';

require_login();

$invoice_id = param('id');
$position = param('pos');
elevate($invoice_id, $position);

redirect('edit');