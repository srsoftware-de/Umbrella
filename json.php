<?php include 'controller.php';

require_login('project');

$options = [];

$ids = param('ids',[]);

$id = param('id');
if (!empty($id)){
	if (empty($ids)){ // if ids is not set: use id as ids
		$ids = $id;
	} else { // if ids is set...
		if (is_array($ids)){ //...and is an array: add id
			$ids[] = $id;
			$ids = array_unique($ids);
		} else if ($ids != $id) $ids = [ $id, $ids ]; // ... and is not an array: combine to array with id
	}
}

if (!empty($ids)) $options['ids']=$ids;

$company_ids = param('company_ids');
if (!empty($company_ids)) $options['company_ids'] = $company_ids;

if ($users = param('users')) {
	if ($users == 'only') die(json_encode(Project::connected_users($options)));
	$options['users'] = $users;
}
die(json_encode(Project::load($options)));