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
$_GET['silent'] = 'on'; 

if (isset($services['bookmark'])){
    $location = str_replace('convert', 'view',location('*'));
    $hash = sha1($location);
    $bookmark = request('bookmark',$hash.'/json');
}

$new_project = request('project','add',['name'=>$task->name,'description'=>$task->description,'company'=>$task->project('company_id'),'from'=>'task'],false);
$pid = $new_project['id'];
foreach ($task->users() as $uid =>$usr) request('project','add_user',['id'=>$pid,'new_user_id'=>$uid],false);

foreach ($task->children(false,true) as $tid => $child) {
    $child->patch(['parent_task_id'=>null])->save();
}
$task->update_project($new_project['id']);

if (isset($services['notes'])) request('notes','task:'.$task_id.'/update_uri?new=project:'.$new_project['id']);

if ($bookmark){
    request('bookmark','edit',['id'=>$hash,'url'=>getUrl('project',$new_project['id'].'/view'),'tags_string'=>$bookmark['tags'],comment=>$bookmark['comment']]);
}


$task->delete();
redirect(getUrl('project',$new_project['id'].'/view'));