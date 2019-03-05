<?php include 'controller.php';

require_login('model');

$process_id = param('id');
if (empty($process_id)) throw new Exception('No process id passed!');

$conn_id = param('elem');
if (empty($conn_id)) throw new Exception('No connector id passed!');

$terminal = ConnectorInstance::load($process_id,$conn_id);
$terminal->patch($_POST)->save();