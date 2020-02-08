<?php include 'controller.php';

require_login('wiki');

$options = [];
if ($ids      = param('ids')) {
	$options['ids'] = $ids;
}
die(json_encode(Page::load($options)));

