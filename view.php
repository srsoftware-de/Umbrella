<?php include_once 'controller.php';

require_login('project');

$show_confirm_question = false;

$project_id = param('id');
if (empty($project_id)){
	error('No project id passed!');
	redirect(getUrl('project'));
}

$project = Project::load(['ids'=>$project_id,'users'=>true]);
if (empty($project)){
	error('You are not member of this project!');
	redirect(getUrl('project'));
}

$title = t('Umprella: Project ◊',$project->name);
$current_user_is_owner = $project->users[$user->id]['permission'] == PROJECT_PERMISSION_OWNER;

if ($remove_user_id = param('remove_user')){
	if ($current_user_is_owner){
		if (param('confirm')==='yes'){
			$project->remove_user($remove_user_id);
		} else {
			$show_confirm_question = true;
		}
	} else error('You are not allowed to remove users from this project');
}

$show_closed_tasks = $project->show_closed > 0 || param('closed') == 'show';
$tasks = [];
try {
	$tasks = request('task','json',['order'=>'name','project_ids'=>$project_id,'load_closed'=>$show_closed_tasks]);
} catch (Exception $ex){}

if (param('note_added')) $project->send_note_notification();

if ($project->company_id > 0 && isset($services['company'])) $project->company = request('company','json',['ids'=>$project->company_id]);

function display_tasks($task_list,$parent_task_id,$parent_show_closed = false){
	global $show_closed_tasks,$project_id;
	$first = true;
	foreach ($task_list as $tid => $task){
		if (!$show_closed_tasks && ($task['status']>=60) && !$parent_show_closed) continue;
		if ($task['parent_task_id'] != $parent_task_id) continue;
		$redirect = urlencode(location());
		if ($first){
			$first = false; ?><ul><?php
		} ?>
		<li class="<?= task_state($task['status'])?>">
			<a href="<?= getUrl('task', $tid.'/view'); ?>"><?= $task['name'] ?></a>
			<?php if (isset($task['est_time']) && $task['est_time']>0) { ?>
			(<?= $task['est_time']?>&nbsp;h)
			<?php } ?>
			<span class="hover_h symbol">
			<a title="<?= t('edit') ?>" href="../../task/<?= $tid ?>/edit?redirect=<?= $redirect ?>"></a>
			<a title="<?= t('add subtask') ?>" 	href="../../task/<?= $tid ?>/add_subtask"> </a>
			<?php if ($task['status'] != TASK_STATUS_STARTED) { ?>
			<a title="<?= t('started') ?>"  href="../../task/<?= $tid ?>/start?redirect=<?= $redirect ?>"></a>
			<?php } if ($task['status'] != TASK_STATUS_COMPLETE) { ?>
			<a title="<?= t('complete') ?>" href="../../task/<?= $tid ?>/complete?redirect=<?= $redirect ?>"></a>
			<?php } if ($task['status'] != TASK_STATUS_CANCELED) { ?>
			<a title="<?= t('cancel') ?>"   href="../../task/<?= $tid ?>/cancel?redirect=<?= $redirect ?>"></a>
			<?php } if ($task['status'] != TASK_STATUS_OPEN) { ?>
			<a title="<?= t('open') ?>"     href="../../task/<?= $tid ?>/open?redirect=<?= $redirect ?>"></a>
			<?php } if ($task['status'] != TASK_STATUS_PENDING) { ?>
			<a title="<?= t('wait') ?>"     href="../../task/<?= $tid ?>/wait?redirect=<?= $redirect ?>"></a>
			<?php } ?>
			<a title="<?= t('add user') ?>" href="../../task/<?= $tid ?>/add_user"> </a>
			<a title="<?= t('delete') ?>"   href="../../task/<?= $tid ?>/delete?redirect=<?= $redirect ?>"></a>
			</span>
			<?php display_tasks($task_list,$tid,$task['show_closed']==1)?>
		</li>
		<?php
	}
	if (!$first){
		?></ul><?php
	}
	return !$first;
}

$est_time = 0;
foreach ($tasks as $task) $est_time += empty($task['est_time']) ? 0 : $task['est_time'];

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include '../common_templates/messages.php';

if ($project){
	if ($show_confirm_question){ ?>
<fieldset>

	<legend><?= t('Confirm removal of "◊" from project?',$project->users[$remove_user_id]['data']['login'])?></legend>
	<?= t('User will no longer have access to this projects. Task assigned to "◊" will be assigned to you. Are you sure?',$project->users[$remove_user_id]['data']['login'])?><br/>
	<a class="button" href="?remove_user=<?= $remove_user_id?>&confirm=yes"><?= t('Yes')?></a>
	<a class="button" href="view"><?= t('No')?></a>
</fieldset>
<?php }
?>
<table class="vertical project-view">
	<tr>
		<th><?= t('Project')?></th>
		<td>
			<span class="right symbol">
				<?php if ($project->status != PROJECT_STATUS_COMPLETE ) { ?><a title="<?= t('complete')?>" href="complete?redirect=../index"></a><?php } ?>
				<?php if ($project->status != PROJECT_STATUS_CANCELED ) { ?><a title="<?= t('cancel')?>" href="cancel?redirect=../index"></a><?php } ?>
				<?php if (!in_array($project->status, [PROJECT_STATUS_OPEN,PROJECT_STATUS_PENDING]) ) { ?><a title="<?= t('open')?>" href="open?redirect=../index"></a><?php } ?>
				<a title="<?= t('edit') ?>" href="edit"></a>
				<a title="<?= t('add task')?>" href="../../task/add_to_project/<?= $project->id ?>"> </a>
				<a title="<?= t('export project') ?>" href="export"></a>
				<a title="<?= t('export as JSON') ?>" href="json_export"></a>
				<a title="<?= t('add user')?>" href="add_user"></a>
				<?php
					try {
						$transform = getUrl('task','from_project?id='.$project_id);
					?><a title="<?= t('Transform to task')?>" href="<?= $transform ?>">?</a><?php
					} catch (Exception $ex){} ?>
			</span>
			<h1><?= $project->name ?></h1><?= ' ('.t(project_state($project->status)).')' ?>
		</td>
	</tr>
	<?php if (!empty($project->company)) { ?>
	<tr>
		<th><?= t('Company') ?></th>
		<td><a href="<?=getUrl('company')?>"><?= $project->company['name'] ?></a></td>
	</tr>
	<?php } ?>
	<tr>
		<th><?= t('Related') ?></th>
		<td>
			<?php if (isset($services['files'])) { ?>
			<a href="<?= getUrl('files','?path=project/'.$project->id) ?>"><span class="symbol"></span> <?= t('files') ?></a>&nbsp;
			<?php }  if (isset($services['model'])) { ?>
			<a href="<?= getUrl('model','?project='.$project->id) ?>"><span class="symbol"></span> <?= t('models') ?></a>&nbsp;
			<?php }  if (isset($services['time'])) { ?>
			<a href="<?= getUrl('time','?project='.$project->id) ?>"><span class="symbol"></span> <?= t('timetracking') ?></a>&nbsp;
			<?php } ?>
		</td>
	</tr>
	<tr>
		<th><?= t('Description')?></th><td><?= markdown($project->description) ?></td>
	</tr>
	<?php if ($est_time) { ?>
	<tr>
		<th><?= t('Estimated time')?></th><td><?= t('◊ hours',$est_time) ?></td>
	</tr>

	<?php }

	try {
	$add_url = getUrl('task','add_to_project/'.$project->id); ?>

	?>
	<tr>
		<th>
			<?= t('Tasks')?>
			<?php if (!$show_closed_tasks) { ?>
			<a class="symbol" href="?closed=show" title="<?= t('show closed tasks')?>"></a>
			<?php } ?>
			<br/><br/>
			<a href="gantt"><?= t('Gantt chart')?></a>
		</th>
		<td class="tasks">
		<?php
		if ($tasks) $tasks = display_tasks($tasks, null);
		if (!$tasks){ ?>
			<a class="symbol" href="<?= $add_url ?>">?</a>
			<a href="<?= $add_url ?>"><?= t('add task') ?></a>
		<?php } ?>
		</td>
	</tr>
	<?php } catch (Exception $ex) {}
	if ($project->users){ ?>
	<tr>
		<th><?= t('Users')?>
		<?php if (isset($services['rtc'])) { ?>
		<a class="symbol" target="_blank" title="<?= t('Start conversation with all users of this project'); ?>" href="<?= getUrl('rtc','open?users='.implode(',',array_keys($project->users))) ?>"></a>
		<?php } ?>
		</th>
		<td>
			<ul>
			<?php foreach ($project->users as $uid => $usr) { ?>
				<li>
					<?= $usr['data']['login'].' ('.t($PROJECT_PERMISSIONS[$usr['permission']]).')'; ?>
					<?php if ($uid != $user->id) {
						if (isset($services['rtc'])) { ?><a class="symbol" title="<?= t('Start conversation'); ?>" target="_blank" href="<?= getUrl('rtc','open?users='.$uid) ?>"></a><?php }
						if ($current_user_is_owner) { ?><a class="symbol" title="<?= t('remove ◊ from project',$usr['data']['login']) ?>" href="?remove_user=<?= $uid ?>"></a><?php } ?>
					<?php } // user matches?>
				</li>
			<?php } ?>
			</ul>
		</td>
	</tr>
	<?php } ?>
</table>
<?= isset($services['bookmark']) ? request('bookmark','html',['hash'=>sha1(location('*'))],false,NO_CONVERSION) : '' ?>
<?= isset($services['notes']) ? request('notes','html',['uri'=>'project:'.$project_id,'context'=>t('Project "◊"',$project->name),'users'=>array_keys($project->users)],false,NO_CONVERSION) : '' ?>
<?php }

include '../common_templates/closure.php'; ?>
