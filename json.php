<?php include 'controller.php';
require_login('poll');

$options      = ['open'=>true];
if ($ids      = param('ids')) {
	$options['ids'] = $ids;
} elseif ($ids = param('id')){
	$options['ids'] = $ids;
}
die(json_encode(Poll::load($options)));
