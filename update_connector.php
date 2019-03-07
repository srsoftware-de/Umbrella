<?php include 'controller.php';

require_login('model');

$process_connector_id = param('id');
if (empty($process_connector_id)) throw new Exception('No process_connector id passed!');

$process_place_id = param('place_id');
if (!empty($process_place_id)) {
	Connector::updatePlace($process_connector_id,$process_place_id,param('angle'));
} else Process::updateConnector(['process_connector_id'=>$process_connector_id,'angle'=>param('angle')]);