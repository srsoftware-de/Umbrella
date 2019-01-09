<?php include 'controller.php';
require_login('task');

$project_id = post('project_id');
$user_id = post('user_id');

assert($project_id !== null,'Project id must be set!');
assert($user_id !== null,'User id must be set!');

withdraw_user($user_id,$project_id);