<?php include 'controller.php';

require_login('project');

if ($project_id = param('id')) {
	$project = Project::load(['ids'=>$project_id]);
	$project->patch(['status'=>PROJECT_STATUS_COMPLETE])->save();
	if ($redirect=param('redirect')){
		redirect($redirect);
	} else {
		redirect('view');
	}
} else {
	error('No project id passed!');
	redirect(getUrl('project'));
}
