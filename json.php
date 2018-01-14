<?php $title = 'Umbrella Invoice Management';

include '../bootstrap.php';
include 'controller.php';

require_login('invoice');
$options = [];
if ($times = param('times')) $options['times'] = $times;

die(json_encode(Invoice::load($options)));