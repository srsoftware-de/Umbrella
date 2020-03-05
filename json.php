<?php include 'controller.php';
require_login('poll');

if ($ids = param('ids',param('id'))) $options['ids'] = $ids;
die(json_encode(Poll::load($options)));
