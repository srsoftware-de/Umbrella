<?php 

include '../bootstrap.php';
include 'controller.php';

require_login('project');
$project_id = param('id');
if ($project_ids = param('ids')){
	$project_ids = explode(',', $project_ids);
	die(json_encode(load_projects($project_ids)));
}

if (!$project_id) error('No project id passed to view!');

die(json_encode(load_projects($project_id)));
