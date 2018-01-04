<?php

include '../bootstrap.php';
include 'controller.php';

require_login('project');
$project_ids = param('id');
assert($project_ids !== null,'No project id passed to view!');
if (strpos($project_ids, ',')!==false) $project_ids = explode(',', $project_ids);


if (is_array($project_ids)){
	$projects = load_projects(['ids'=>$project_ids]);
	$user_ids = load_users($projects);
	die(json_encode($user_ids));
} else {
	$projects = load_projects(['ids'=>$project_ids,'single'=>true]);
	load_users($projects);
	die(json_encode($projects['users']));
}