<?php $title = 'Umbrella Task Management';

include '../bootstrap.php';
include 'controller.php';
require_login('task');

function child_update_project(&$task,$new_project_id){
	if ($task['project_id']>0)	$task['project_id'] = $new_project_id;
	if (isset($task['children'])) {
		foreach ($task['children'] as &$child) child_update_project($child,$new_project_id);
	}
	update_task($task);
}

$task_id = param('id');
if (!$task_id) {
	error('No task id passed!');
	redirect(getUrl('task'));
}

$task = load_tasks(['ids'=>$task_id]);
if ($task){
	$project_id = find_project($task_id);
	$project = request('project','json',['ids'=>$task['project_id']]);
	$new_project = request('project','add',['name'=>$task['name'],'description'=>$task['description'],'company'=>$project['company_id'],'from'=>'task']);
	load_children($task,99);
	foreach ($task['children'] as &$child_task){
		$child_task['parent_task_id'] = null;
		child_update_project($child_task,$new_project['id']);
	}
	delete_task($task);
	redirect(getUrl('project',$new_project['id'].'/view'));
}
error('Something went wrong!');
redirect(getUrl('task',$task_id.'/view'));