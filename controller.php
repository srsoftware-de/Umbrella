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

	$data = new class (){};
	$data->task = $task;
	$data->title = $task->name.' - Umbrella';

	$data->show_closed_children = $task->show_closed == 1 || param('closed') == 'show';
	$data->bookmark = false;
	if (isset($services['bookmark'])){
		$hash = sha1(location('*'));
		$data->bookmark = request('bookmark',$hash.'/json');
	}

	if (empty($task->parent())){
		$siblings = Task::load(['project_ids'=>$task->project_id,'parent_task_id'=>null]);
	} else {
		$siblings = $task->parent()->children();
	}
	$data->previous = null;
	$data->next = null;
	$last = null;

	foreach ($siblings as $sibling){
		if ($sibling->status > 50) continue;
		if ($last != null && $last->id == $task->id) $data->next = $sibling;
		if ($sibling->id == $task->id) $data->previous = $last;
		$last = $sibling;
	}

	return $data;
}
?>
