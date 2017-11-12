<?php

include '../bootstrap.php';
include 'controller.php';

require_login('project');
$project_ids = param('id');

if (!$project_ids) error('No project id passed to view!');
echo json_encode(load_users($project_ids));
