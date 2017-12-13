<?php $title = 'Umbrella Users';

include '../bootstrap.php';
include 'controller.php';

require_login('company');

if ($ids = param('ids')) die(json_encode(Company::load($ids)));

die(json_encode(Company::load()));