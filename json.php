<?php include 'controller.php';

User::require_login();

$data = User::load(['ids'=>param('ids',param('id')),'target'=>'json']);
if (empty($data)) {
	http_response_code(400);
	die(t('No such user'));
}
die(json_encode($data));
