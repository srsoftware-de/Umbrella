<?php include 'controller.php';

require_login('items');

$company_id = param('company');
$options = ['company_id'=>$company_id];
if ($order = param('order')) $options['order']=$order;

assert($company_id !== null,'No company id supplied!');

die(json_encode(Item::load($options)));