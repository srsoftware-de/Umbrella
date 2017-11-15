<?php $title = 'Umbrella Users';

include '../bootstrap.php';
include 'controller.php';

require_login('task');

$selection = [];
if ($order = param('order')) $selection['order']=$order;
if ($project = param('project')) $selection['project_id'] = $project;
die(json_encode(get_tasks($selection)));
