<?php include 'controller.php';
require_login('task');

$project_id = post('project_id');

if (empty($project_id)) throw new Exception('Project id must be set!');
$project = request('project','json',['ids'=>$project_id]);
if (empty($project)) throw new Exception('You don`t have access to that project!');

$user_id = post('user_id');
if (empty($user_id)) throw new Exception('User id must be set!');

Task::withdraw_user_from_project($user_id,$project_id);