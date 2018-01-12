<?php $title = 'Umbrella Invoice Management';

include '../bootstrap.php';
include 'controller.php';

require_login('invoice');
$options = [];
if ($times = param('times')) $options['times'] = $times;
$invoices = Invoice::load($options);

die(json_encode($invoices));