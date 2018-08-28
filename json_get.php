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
$bookmark = Bookmark::load(['url_hash'=>$url_hash]);
if (!empty($bookmark)) echo $bookmark->json();