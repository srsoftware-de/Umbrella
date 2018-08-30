<?php include 'controller.php';

require_login('project');

$options = [];

if ($project_ids = param('ids')) $options['ids'] = $project_ids;
if ($company_ids = param('company_ids')) $options['company_ids'] = $company_ids;

if ($users = param('users')) {
	if ($users == 'only') die(json_encode(Project::connected_users($options)));
	$options['users'] = $users;
}
die(json_encode(Project::load($options)));