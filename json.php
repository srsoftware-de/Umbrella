<?php $title = 'Umbrella Users';

include '../bootstrap.php';
include 'controller.php';

require_login('company');

$options = [];
if ($ids = param('ids')) $options['ids'] = $ids;
if ($single = param('single')) $options['single'] = $single; 

$company = Company::load($options);

if ($single && param('users')==1) $company->users();
die(json_encode($company));