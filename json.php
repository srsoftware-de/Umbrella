<?php include 'controller.php';

require_login('document');
$options = [];
if ($times = param('times')) $options['times'] = $times;
if ($ids = param('ids')) $options['ids'] = $ids;

die(json_encode(Document::load($options)));