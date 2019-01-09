<?php include 'controller.php';
require_login('task');

if ($task_id = param('id')){
	$task = task::load(['ids'=>$task_id]);
	if ($task){
		$new_project = request('project','add',['name'=>$task->name,'description'=>$task->description,'company'=>$task->project('company_id'),'from'=>'task']);
		$task->update_project($new_project['id']);
		if (isset($services['notes'])) request('notes','task:'.$task_id.'/update_uri?new=project:'.$new_project['id']);
		$task->delete();
		redirect(getUrl('project',$new_project['id'].'/view'));
	}
	error('Something went wrong!');
	redirect(getUrl('task',$task_id.'/view'));
} else {
	error('No task id passed!');
	redirect(getUrl('task'));
}

