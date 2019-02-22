<?php include 'controller.php';

require_login('bookmark');

$url = param('url');
$tags = param('tags');
if ($url && $tags) {
	$bookmark = Bookmark::add($url, $tags, param('comment'));
	if ($share_users = param('users')){
		foreach ($share_users as $u => $setting){
			switch ($setting){
				case SHARE_AND_NOTIFY:
					$bookmark->share($u,true);
					break;
				case SHARE_DONT_NOTIFY:
					$bookmark->share($u,false);
					break;
			}
		}
	}
	redirect(getUrl('bookmark')); // show last bookmarks
} else if ($url){
	error(t('Please set at least one tag!'));
} else if ($tags) {
	error(t('Please set url!'));
}

$users = load_connected_users();

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php';

include 'addform.php'; 

include '../common_templates/closure.php'; ?>