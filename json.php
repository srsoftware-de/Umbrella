<?php 

include '../bootstrap.php';
include 'controller.php';

require_login('project');

$options = [];

if ($project_ids = param('ids')) $options['ids'] = explode(',', $project_ids);
if ($company_ids = param('company_ids')) $options['company_ids'] = explode(',', $company_ids);
if ($single = param('single')) $options['single'] = $single;

die(json_encode(load_projects($options)));
