<?php include 'controller.php';

require_login('bookmark');
$url_hash = param('id');

if ($url = param('url')) $url_hash = sha1($url);
if ($url_hash) $bookmark = Bookmark::load(['url_hash'=>$url_hash]);
if (!empty($bookmark)) echo $bookmark->json();

if ($tag = param('tag')) {
    $tag = Tag::load(['tag'=>$tag]);
    if ($tag) echo json_encode($tag->bookmarks());
}