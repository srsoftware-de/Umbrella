<?php $title = 'Umbrella Users';

include '../bootstrap.php';
include 'controller.php';

require_login('company');

$options = [];
if ($ids = param('ids')) $options['ids'] = $ids;
if ($users = param('users')){
	if ($users == 'only') die(json_encode(Company::connected_users($options)));
	$options['users'] = $users;
}
die(json_encode(Company::load($options)));