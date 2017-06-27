<?php 

include '../bootstrap.php';
include 'controller.php';

require_login();
$project_id = param('id');

if (!$project_id) error('No project id passed to view!');

echo json_encode(load_project($project_id));
