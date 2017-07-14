<?php $title = 'Umbrella Project Management';

include '../bootstrap.php';
include 'controller.php';
require_login();

$project_id = param('id');
if (!$project_id) error('No project id passed!');
set_project_state($project_id,PROJECT_STATUS_COMPLETE);
if ($redirect=param('redirect')){
	redirect($redirect);
} else {
	redirect('view');
}