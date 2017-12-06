<?php $title = 'Umbrella Users';

include '../bootstrap.php';
include 'controller.php';

require_login('company');

$companies = Company::load();

echo json_encode($companies);