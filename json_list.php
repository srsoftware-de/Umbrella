<?php $title = 'Umbrella Item Management';

include '../bootstrap.php';
include 'controller.php';

require_login('items');

$company_id = param('company');
assert($company_id !== null,'No company id supplied!');

echo json_encode(Item::load($company_id));