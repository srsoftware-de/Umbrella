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

$title = $task->name.' - Umbrella';
$show_closed_children = $task->show_closed == 1 || param('closed') == 'show';
$bookmark = false;
if (isset($services['bookmark'])){
	$hash = sha1(location('*'));
	$bookmark = request('bookmark',$hash.'/json');
}

function display_children($task){
	global $show_closed_children,$task_id,$services;
	if (empty($task->children())) return; ?>
	<ul>
	<?php foreach ($task->children() as $id => $child_task) {
			if (!$show_closed_children && $child_task->status >= 60) continue;
		?>
		<li class="<?= task_state($child_task->status) ?>">
			<a title="<?= t('view')?>"		href="../<?= $id ?>/view"><?= $child_task->name?></a>
			<span class="hover_h">
			<a title="<?= t('edit')?>"			href="../<?= $id ?>/edit?redirect=../<?= $task_id ?>/view"     class="symbol"></a>
			<a title="<?= t('add subtask')?>"	href="../<?= $id ?>/add_subtask" class="symbol"></a>
			<a title="<?= t('complete')?>"		href="../<?= $id ?>/complete?redirect=../<?= $task_id ?>/view" class="<?= $child_task->status == TASK_STATUS_COMPLETE ? 'hidden':'symbol'?>"></a>
			<a title="<?= t('cancel')?>"		href="../<?= $id ?>/cancel?redirect=../<?= $task_id ?>/view"   class="<?= $child_task->status == TASK_STATUS_CANCELED ? 'hidden':'symbol'?>"></a>
			<a title="<?= t('start')?>"			href="../<?= $id ?>/start?redirect=../<?= $task_id ?>/view"    class="<?= $child_task->status == TASK_STATUS_STARTED  ? 'hidden':'symbol'?>"></a>
			<a title="<?= t('open')?>"			href="../<?= $id ?>/open?redirect=../<?= $task_id ?>/view"     class="<?= $child_task->status == TASK_STATUS_OPEN     ? 'hidden':'symbol'?>"></a>
			<a title="<?= t('wait')?>"			href="../<?= $id ?>/wait?redirect=../<?= $task_id ?>/view"	   class="<?= $child_task->status == TASK_STATUS_PENDING  ? 'hidden':'symbol'?>"></a>

			<?php if (isset($services['time'])) { ?>
				<a class="symbol" title="<?= t('add to timetrack')?>" href="<?= getUrl('time','add_task?tid='.$id); ?>"></a>
				<?php } ?>
			</span>
			<?php display_children($child_task);?>
		</li>
	<?php }?>
	</ul>
	<?php
}

if (empty($task->parent())){
	$siblings = Task::load(['project_ids'=>$task->project_id,'parent_task_id'=>null]);
} else {
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

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<?php if ($task) { ?>
<table class="vertical tasks">
	<tr>
		<th><?= t('Project')?></th>
		<td>
			<span class="project">
				<a href="<?= getUrl('project',$task->project_id.'/view'); ?>"><?= $task->project('name')?></a>
			</span>
			&nbsp;&nbsp;&nbsp;&nbsp;
			<a href="<?= getUrl('files').'?path=project/'.$task->project_id ?>" title="<?= t('show project files'); ?>" target="_blank"><span class="symbol"></span> <?= t('Files')?></a>


		</td>
	</tr>
	<tr>
		<th>
			<?= t('Navigation')?>
		</th>
		<td class="navi">
			<?= isset($previous) ? '<a href="'.getUrl('task',$previous->id.'/view').'" title="'.t('go to previous task').'"><span class="symbol"></span>&nbsp;'.$previous->name.'</a>':'' ?> |
			<?= !empty($task->parent()) ? '<a href="'.getUrl('task',$task->parent()->id.'/view').'" title="'.t('go to next task').'"><span class="symbol"></span>&nbsp;'.$task->parent()->name.'</a>':'' ?> |
			<?= isset($next) ? '<a href="'.getUrl('task',$next->id.'/view').'" title="'.t('go to next task').'">'.$next->name.'&nbsp;<span class="symbol"></span></a>':'' ?>
		</td>
	</tr>
	<tr>
		<th><?= t('Task')?></th>
		<td>
			<h1><?= $task->name ?></h1>
			<span class="right">
			<?php if ($task->is_writable()) { ?>
				<a title="<?= t('edit')?>"         href="edit"		  class="symbol"></a>
				<a title="<?= t('add subtask')?>"  href="add_subtask" class="symbol"> </a>
				<a title="<?= t('add user')?>"     href="add_user"    class="symbol"></a>
				<a title="<?= t('start')?>"        href="start"       class="<?= $task->status == TASK_STATUS_STARTED  ? 'hidden':'symbol'?>"></a>
				<a title="<?= t('complete')?>"     href="complete"    class="<?= $task->status == TASK_STATUS_COMPLETE ? 'hidden':'symbol'?>"></a>
				<a title="<?= t('cancel')?>"       href="cancel"      class="<?= $task->status == TASK_STATUS_CANCELED ? 'hidden':'symbol'?>"></a>
				<a title="<?= t('open')?>"         href="open"        class="<?= $task->status == TASK_STATUS_OPEN     ? 'hidden':'symbol'?>"></a>
				<a title="<?= t('wait')?>"         href="wait"        class="<?= $task->status == TASK_STATUS_PENDING  ? 'hidden':'symbol'?>"></a>
				<a title="<?= t('delete')?>"       href="delete"      class="symbol"></a>
				<a title="<?= t('convert to project'); ?>" href="convert" class="symbol"></a>
				<?php } // task writeable ?>
				<a title="<?= t('export task') ?>" href="export"      class="symbol" ></a>
				<?php if (isset($services['time'])) { ?>
				<a class="symbol" title="<?= t('add to timetrack')?>" href="<?= getUrl('time','add_task?tid='.$task_id); ?>"></a>
				<?php } ?>
			</span>
		</td>
	</tr>
	<?php if ($task->description){ ?>
	<tr>
		<th><?= t('Description')?></th>
		<td class="description"><?= markdown($task->description); ?></td>
	</tr>
	<?php } ?>
	<?php if (
			(!empty($task->est_time)) || (!empty($task->child_time()))){ ?>
	<tr>
		<th><?= t('Estimated time')?></th>
		<td>
			<?php if (!empty($task->est_time)){ ?>
			<?= t('◊ hours',$task->est_time)?>
			<br/>
			<?php } ?>
			<?php if (!empty($task->child_time())){ ?>
			<?= t('Sub-tasks: ◊ hours',$task->child_time())?>
			<?php } ?>
		</td>
	</tr>
	<?php } ?>
	<?php if (!empty($task->start_date)) { ?>
	<tr>
		<th><?= t('Start date')?></th>
		<td><?= $task->start_date ?></td>
	</tr>
	<?php } ?>
	<?php if (!empty($task->due_date)) { ?>
	<tr>
		<th><?= t('Due date')?></th>
		<td><?= $task->due_date ?></td>
	</tr>
	<?php } ?>
	<?php if (!empty($task->requirements())) { ?>
	<tr>
		<th><?= t('Prerequisites')?></th>
		<td class="requirements">
			<ul>
			<?php foreach ($task->requirements() as $id => $required_task) {?>
				<li <?= in_array($required_task->status,[TASK_STATUS_CANCELED,TASK_STATUS_COMPLETE])?'class="inactive"':''?>><a href="../<?= $id ?>/view"><?= $required_task->name ?></a></li>
			<?php } ?>
			</ul>
		</td>
	</tr>
	<?php } ?>
	<?php if (!empty($task->children())){?>
	<tr>
		<th>
			<?= t('Child tasks')?>

		</th>
		<td class="children">
			<?php if (!$show_closed_children) {?>
			<a href="?closed=show"><span class="symbol"></span> <?= t('show closed child tasks'); ?></a>
			<?php } ?>
			<?php display_children($task); ?>
		</td>
	</tr>
	<?php } ?>
	<?php if (!empty($task->users())){ ?>
	<tr>
		<th>
			<?= t('Users')?>
			<?php if (isset($services['rtc'])) { ?>
			<a class="symbol" target="_blank" title="<?= t('Start conversation with all users of this task') ?>" href="<?= getUrl('rtc','open?users='.implode(',',array_keys($task->users()))) ?>"></a>
			<?php } ?>
		</th>
		<td>
			<ul>
			<?php foreach ($task->users() as $uid => $u) { ?>
				<li>
					<?= $u['login'] ?>
					(<?= Task::perm_name($u['permissions']) ?>)
					<?php if (isset($services['rtc']) && $uid != $user->id) { ?><a class="symbol" target="_blank" title="<?= t('Start conversation') ?>" href="<?= getUrl('rtc','open?users='.$uid) ?>"></a><?php } ?>
					<?php if ( // deletion of user only possible if:
						($task->users[$user->id]['permissions'] == Task::PERMISSION_CREATOR || $uid == $user->id) // only owner of task may remove other users ; user may remove himself/herself from task
						&& $u['permissions'] != Task::PERMISSION_CREATOR){ // but only if user to be removed is not owner ?>
					<a class="symbol" title="<?= t('Assign user to task') ?>" href="assign_user?uid=<?= $uid ?>"></a>
					<a class="symbol" title="<?= t('De-assign user from task') ?>" href="drop_user?uid=<?= $uid ?>"></a>
					<?php } ?>
				</li>
			<?php } ?>
			</ul>
		</td>
	</tr>
	<?php } ?>
	<?php if ($bookmark && !empty($bookmark['tags'])) { ?>
	<tr>
		<th><?= t('Tags')?></th>
		<td>
		<?php $base_url = getUrl('bookmark');
		foreach ($bookmark['tags'] as $tag){ ?>
			<a class="button" href="<?= $base_url.$tag.'/view' ?>"><?= $tag ?></a>
		<?php } ?>
		</td>
	</tr>
	<?php } ?>
</table>
<?php if (isset($services['notes'])) echo request('notes','html',['uri'=>'task:'.$task_id,'context'=>t('Task "◊"',$task->name),'users'=>array_keys($task->users())],false,NO_CONVERSION);
} // if task
include '../common_templates/closure.php'; ?>
