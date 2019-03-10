<?php include 'controller.php';

require_login('model');

$place_id = param('place_id');
if (empty($place_id)) throw new Exception('You need to specify a place id!');


$type = param('type');
switch ($type){
	case 'process':
		if (!Process::load(['process_place_id'=>$place_id])->project()) throw new Exception('You are not allowed to access this process!');
		Connector::removePlaces(['process_place_id'=>$place_id]);
		$sql = 'DELETE FROM process_places WHERE id = :id';
		if (!get_or_create_db()->prepare($sql)->execute([':id'=>$place_id])) throw new Exception('Was not able to remove process from context!');
		break;
	case 'terminal':
		if (!Terminal::load(['terminal_place_id'=>$place_id])->project()) throw new Exception('You are not allowed to access this terminal!');
		Flow::removeTerminalFlows([$place_id]);
		$sql = 'DELETE FROM terminal_places WHERE id = :id';
		if (!get_or_create_db()->prepare($sql)->execute([':id'=>$place_id])) throw new Exception('Was not able to remove terminal from context!');
		break;
	default:
		throw new Exception('Invalid place type!');
}


