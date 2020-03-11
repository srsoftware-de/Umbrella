<?php include 'controller.php';

require_login('stock');

$item_id = param('id',param('ids'));
if ($item_id === null) throw new Exception('Called stock/json without item id');
$item = Item::load(['ids'=>$item_id]);
echo json_encode($item);
