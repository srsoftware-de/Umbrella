<?php include 'controller.php';

require_login('project');

if ($project_id = param('id')) {
	$project = Project::load(['ids'=>$project_id]);
	if (!empty($project)){
		$project->patch(['status'=>PROJECT_STATUS_COMPLETE])->save();
		redirect(param('redirect',getUrl('project',$project_id.'/view')));
	} else error('You are not member of this project!');
} else error('No project id passed!');

redirect(getUrl('project'));