<?php include 'controller.php';

User::require_login();

if ($service_id = param('id')){
	LoginService::deassign($service_id);
} else {
	error('No id passed to de-assign function!');
}
redirect(getUrl('user'));
