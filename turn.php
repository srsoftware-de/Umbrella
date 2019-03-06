<?php include 'controller.php';

require_login('model');

$connection_id = param('id');

$flow = Connection::load(['ids'=>$connection_id]);

$connection = new Connection();

$connection->id = $flow->id;
$connection->dirty = ['id'];
if (!empty($flow->start_connector)) $connection->patch(['end_connector'=>$flow->start_connector]);
if (!empty($flow->end_connector))   $connection->patch(['start_connector'=>$flow->end_connector]);
if (!empty($flow->start_terminal))  $connection->patch(['end_terminal'=>$flow->start_terminal]);
if (!empty($flow->end_terminal))    $connection->patch(['start_terminal'=>$flow->end_terminal]);
$connection->patch(['flow_id'=>$flow->id()]);
$connection->save();
redirect(getUrl('model'));