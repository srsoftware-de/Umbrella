<?php include 'controller.php';
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

if (isset($services['bookmark'])){
    $location = str_replace('convert', 'view',location('*'));
    $hash = sha1($location);
    $bookmark = request('bookmark',$hash.'/json');
}
//debug($bookmark,1);

$new_project = request('project','add',['name'=>$task->name,'description'=>$task->description,'company'=>$task->project('company_id'),'from'=>'task']);
$task->update_project($new_project['id']);

if (isset($services['notes'])) request('notes','task:'.$task_id.'/update_uri?new=project:'.$new_project['id']);

if ($bookmark){
    request('bookmark','edit',['id'=>$hash,'url'=>getUrl('project',$new_project['id'].'/view'),'tags_string'=>$bookmark['tags'],comment=>$bookmark['comment']]);
}


$task->delete();
redirect(getUrl('project',$new_project['id'].'/view'));