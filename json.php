<?php $title = 'Umbrella Users';

include '../bootstrap.php';
include 'controller.php';

require_user_login();

if ($ids = param('ids',param(id))){
	$data = get_userlist($ids);
	if (empty($data)) {
		http_response_code(400);
		die(t('No such user'));
	}
	die(json_encode($data));
} else {
	http_response_code(400);
	die(t('You need to specify a user id!'));
}
