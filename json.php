<?php $title = 'Umbrella Invoice Management';

include '../bootstrap.php';
include 'controller.php';

require_login('invoice');
$invoices = Invoice::load();

die(json_encode($invoices));