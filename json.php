<?php $title = 'Umbrella Users';

include '../bootstrap.php';
include 'controller.php';

require_login('company');

$options = [];
if ($ids = param('ids')) $options['ids'] = $ids;
if ($single = param('single')) $options['single'] = $single; 
	
die(json_encode(Company::load($options)));