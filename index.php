<?php include 'controller.php';

require_login('bookmark');

if ($share_user = param('share_user_id')) {
	$bookmark = Bookmark::load(['url_hash'=>param('share_url_hash')]);
	$bookmark->share($share_user,param('notify'));
}

$bookmarks = Bookmark::load(['order' => 'timestamp DESC', 'limit' => param('limit',40)]);
$legend = t('latest bookmarks');
$users = load_connected_users();
$base_url = getUrl('bookmark');

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php';

include 'addform.php';
include 'list.php';

include '../common_templates/closure.php'; ?>
