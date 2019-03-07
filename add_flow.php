<?php include 'controller.php';

require_login('model');

debug(['$_POST'=>$_POST]);

$origin = param('from');
if (empty($origin)) throw new Exception('add_flow called without "from" parameter!');

$target = param('to');
if (empty($target)) throw new Exception('add_flow called without "to" parameter!');

$origin_connector = Connector::load(['process_connector_id'=>$origin['process_connector_id']]);
if (empty($origin_connector)) throw new Exception('Invalid connector specified for flow origin!');

$project = request('project','json',['ids'=>$origin_connector->project_id]);
if (empty($project)) throw new Exception('The model you want to edit does not belong to one of your projects!');

$target_connector = Connector::load(['process_connector_id'=>$target['process_connector_id']]);
if (empty($target_connector)) throw new Exception('Invalid connector specified for flow target!');

if ($origin_connector->project_id != $target_connector->project_id) throw new Exception('You can not compose flows between connectors in models of different projects!');

$name = param('name');
if (empty($name)) throw new Exception('No name set for flow!');

if (empty($origin['place_id'])){
	if (empty($target['place_id'])) throw new Exception('Either origin or source need to have a place id');
	Flow::add_external($project,$name,$origin,$target,Flow::FROM_BORDER); // from - to - type
} elseif (empty($target['place_id'])){
	Flow::add_external($project,$name,$target,$origin,Flow::TO_BORDER); // from - to - type
} else Flow::add_internal($project,$name,$origin,$target);
