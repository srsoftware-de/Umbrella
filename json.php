<?php include 'controller.php';

require_login('model');

$options      = [];
if ($ids = param('ids')) $options['ids'] = $ids;

die(json_encode(Process::load($options)));
