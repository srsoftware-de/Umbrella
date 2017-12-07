<?php $title = 'Umbrella Bookmark Management';

include '../bootstrap.php';
include 'controller.php';

require_login('bookmark');
$url_hash = param('id');
if (!$url_hash) {
	if ($url = param('url')){
		$url_hash = sha1($url);
	} else error('No url or url hash passed!');
}

$link = load_url($url_hash);

echo json_encode($link);