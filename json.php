<?php 

include '../bootstrap.php';
include 'controller.php';

require_login('project');

$options = [];

if ($project_ids = param('ids')) $options['ids'] = $project_ids;
if ($company_ids = param('company_ids')) $options['company_ids'] = $company_ids;


die(json_encode(load_projects($options)));
