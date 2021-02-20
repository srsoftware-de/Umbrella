<?php include 'model.php';

$base_url = $services['task']['path'];

function easylist() {
    global $services,$base_url;
    require_login('task');
    
    if ($tag = param('tag')) redirect(getUrl('task',$tag.'/easylist'));
    if ($complete_id = param('complete')) request('task',$complete_id.'/complete');
    if ($open_id = param('open')) request('task',$open_id.'/open');
    $view = new stdClass();
    $view->tag = param('id');
    if (empty($view->tag)) return null;
    
    $bookmarks = request('bookmark','json',['tag'=>$view->tag],false,OBJECT_CONVERSION);
    $start = strlen($base_url);
    $task_ids = [];
    foreach ($bookmarks as $bookmark){
        if (strpos($bookmark->url, $base_url) === 0){
            $suffix = substr($bookmark->url, $start);
            $num = explode('/', $suffix)[0];
            $task_ids[$num]=true;
        }
    }
    
    $view->tasks = Task::load(['ids'=>array_keys($task_ids),'order'=>'name']);
    return $view;
}

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
	$previous = null;
	$next = null;

	$last = null;
	foreach ($siblings as $sibling){
		if ($sibling->status > 50) continue;
		if ($last != null && $last->id == $task->id) $next = $sibling;
		if ($sibling->id == $task->id) $previous = $last;
		$last = $sibling;
	}

	$view->navigation = [];

	if (!empty($previous)) $view->navigation[] = (object)['href'=>getUrl('task',$previous->id."/view"),'text'=>$previous->name,'symbol'=>'','hover'=>t('go to previous task')];
	if (empty($task->parent())){
		$view->navigation[] = (object)['href'=> getUrl('project',$task->project->id.'/view'),'text'=>$task->project->name,'symbol'=>'','hover'=>t('go to project'),'class'=>'parent'];
	} else {
		$view->navigation[] = (object)['href'=> getUrl('task',$task->parent->id.'/view'),'text'=>$task->parent->name,'symbol'=>'','hover'=>t('go to parent task'),'class'=>'parent'];
	}
	if (!empty($next)) $view->navigation[] = (object)['href'=>getUrl('task',$next->id."/view"),'text'=>$next->name,'symbol'=>'','hover'=>t('go to next task')];

	return $view;
}
?>
