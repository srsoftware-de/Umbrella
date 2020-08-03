<?php include 'controller.php';

require_login('bookmark');

if ($share_user = param('share_user_id')) {
	$bookmark = Bookmark::load(['url_hash'=>param('share_url_hash')]);
	$bookmark->share($share_user,param('notify'));
}

$id = param('id');
if (!$id) error('No tag passed to view!');

$tag = Tag::load(['tag'=>$id]);
if (!$tag) redirect(getUrl('bookmark'));
$bookmarks = $tag->bookmarks();
$legend = t('Tag "◊"',$tag->tag).' - <a href="'.getUrl('user','search?fulltext=true&key='.$tag->tag).'">'.t('Search for "◊"',$tag->tag).'</a>';
$users = load_connected_users();
$base_url = getUrl('bookmark');

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; 

include 'list.php';

include '../common_templates/closure.php'; ?>
