<?php include 'controller.php';

require_login('model');

$connector_id = param('id');
if (empty($connector_id)) throw new Exception('No connector id passed!');

$place_id = param('place_id');
if (!empty($place_id)) {
	Connector::updatePlace($_POST);
} else Process::updateConnector(['id'=>$connector_id,'angle'=>param('angle')]);