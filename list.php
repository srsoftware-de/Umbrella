<?php $title = 'Umbrella Users';

include '../bootstrap.php';
include 'controller.php';

require_login('project');
echo json_encode(load_projects());