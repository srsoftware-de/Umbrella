<?php include 'model.php';

function view() {
	global $services;

	require_login('task');


	$task_id = param('id');
	if (empty($task_id)){
		error('No task id passed!');
		redirect(getUrl('task'));
	}

	$task = Task::load(['ids'=>$task_id]);
	if (empty($task)){
		error('You don`t have access to that task!');
		redirect(getUrl('task'));
	}

	$task->project(); // load project

	$view = new class (){};
	$view->task = $task;
	$view->title = $task->name.' - Umbrella';

	$view->show_closed_children = $task->show_closed == 1 || param('closed') == 'show';
	$task->children(true,$view->show_closed_children); # load children

	$view->bookmark = false;
	if (isset($services['bookmark'])){
		$hash = sha1(location('*'));
		$view->bookmark = request('bookmark',$hash.'/json');
	}

	// load siblings:
	if (empty($task->parent())){ // either from project, when there is no parent task
		$siblings = Task::load(['project_ids'=>$task->project_id,'parent_task_id'=>null]);
	} else { // or from parent task
		$siblings = $task->parent()->children();
	}
	$view->previous = null;
	$view->next = null;

	$last = null;
	foreach ($siblings as $sibling){
		if ($sibling->status > 50) continue;
		if ($last != null && $last->id == $task->id) $view->next = $sibling;
		if ($sibling->id == $task->id) $view->previous = $last;
		$last = $sibling;
	}

	return $view;
}
?>
