<?php include 'controller.php';

require_login('items');

$company_id = param('company');
$options = ['company_id'=>$company_id];
if ($order = param('order')) $options['order']=$order;

if ($company_id == null) throw new Exception('No company id supplied!');

die(json_encode(Item::load($options)));