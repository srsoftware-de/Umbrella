<?php include 'controller.php';

require_login('model');

$origin = param('from');
if (empty($origin)) throw new Exception('add_flow called without "from" parameter!');

$target = param('to');
if (empty($target)) throw new Exception('add_flow called without "to" parameter!');

$name = param('name');
if (empty($name)) throw new Exception('No name set for flow!');

$flow = null;

if (!empty($origin['terminal_id'])){  // origin is a terminal
	$target_connector = Connector::load(['process_connector_id'=>$target['process_connector_id']]);
	if (empty($target_connector)) throw new Exception('Invalid connector specified for flow target!');

	$project = $target_connector->project();
	if (empty($project)) throw new Exception('The model you want to edit does not belong to one of your projects!');

	$origin_terminal = Terminal::load(['terminal_place_id'=>$origin['place_id']]);
	if (empty($origin_terminal)) throw new Exception('invalid terminal specified for flow origin!');

	if ($target_connector->project_id != $origin_terminal->project_id) throw new Exception('You can not compose flows between endpoints in models of different projects!');

	$type = isset($target['place_id']) ? Flow::FROM_TERMINAL : Flow::TERM_TO_EXT_CON;

	$flow = Flow::add_terminal_flow($project,$name,$target,$origin,$type);
} elseif (!empty($target['terminal_id'])){  // target is a terminal
	$origin_connector = Connector::load(['process_connector_id'=>$origin['process_connector_id']]);
	if (empty($origin_connector)) throw new Exception('Invalid connector specified for flow origin!');

	$project = $origin_connector->project();
	if (empty($project)) throw new Exception('The model you want to edit does not belong to one of your projects!');

	$target_terminal = Terminal::load(['terminal_place_id'=>$target['place_id']]);
	if (empty($target_terminal)) throw new Exception('invalid terminal specified for flow target!');

	if ($origin_connector->project_id != $target_terminal->project_id) throw new Exception('You can not compose flows between endpoints in models of different projects!');

	$type = isset($origin['place_id']) ? Flow::TO_TERMINAL : Flow::EXT_CON_TO_TERM;

	$flow = Flow::add_terminal_flow($project,$name,$origin,$target,$type);
} else { // neither origin nor target are terminals
	$origin_connector = Connector::load(['process_connector_id'=>$origin['process_connector_id']]);
	if (empty($origin_connector)) throw new Exception('Invalid connector specified for flow origin!');

	$project = $origin_connector->project();
	if (empty($project)) throw new Exception('The model you want to edit does not belong to one of your projects!');

	$target_connector = Connector::load(['process_connector_id'=>$target['process_connector_id']]);
	if (empty($target_connector)) throw new Exception('Invalid connector specified for flow target!');

	if ($origin_connector->project_id != $target_connector->project_id) throw new Exception('You can not compose flows between endpoints in models of different projects!');

	if (empty($origin['place_id'])){
		if (empty($target['place_id'])) throw new Exception('Either origin or source need to have a place id');
		$flow = Flow::add_external($project,$name,$origin,$target,Flow::FROM_BORDER); // from - to - type
	} elseif (empty($target['place_id'])){
		$flow = Flow::add_external($project,$name,$target,$origin,Flow::TO_BORDER); // from - to - type
	} else $flow = Flow::add_internal($project,$name,$origin,$target);
}
echo $flow->id;

