<?php 

include '../bootstrap.php';
include 'controller.php';

$user = current_user();
$project_id = param('id');

if (!$project_id) error('No project id passed to view!');
echo json_encode(load_users($project_id));