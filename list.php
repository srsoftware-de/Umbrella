<?php $title = 'Umbrella Users';

include '../bootstrap.php';
include 'controller.php';

require_login('project');
die(json_encode(Project::load()));