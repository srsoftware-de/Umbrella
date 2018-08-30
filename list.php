<?php include 'controller.php';

require_login('project');

die(json_encode(Project::load()));