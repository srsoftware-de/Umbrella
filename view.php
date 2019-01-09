<?php include 'controller.php';
require_login('task');

$task_id = param('id');
$bookmark = false;

if ($task_id){
	if ($task = Task::load(['ids'=>$task_id])){
		$title = $task->name.' - Umbrella';
		$show_closed_children = $task->show_closed == 1 || param('closed') == 'show';
		if ($note_id = param('note_added')) $task->send_note_notification($note_id);
		if (isset($services['bookmark'])){
			$hash = sha1(location('*'));
			$bookmark = request('bookmark','json_get?id='.$hash);
		}
	} else { // task not loaded
		error('Task does not exist or you are not allowed to access it.');
	}
} else /*no task id*/ error('No task id passed to view!');

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

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<?php if ($task) { ?>
<table class="vertical tasks">
	<tr>
		<th><?= t('Task')?></th>
		<td>
			<h1><?= $task->name ?></h1>
			<?php if ($task->is_writable()) { ?>
			<span class="right">

				<a title="<?= t('edit')?>"         href="edit"		  class="symbol"></a>
				<a title="<?= t('add subtask')?>"  href="add_subtask" class="symbol"> </a>
				<a title="<?= t('add user')?>"     href="add_user"    class="symbol"></a>
				<a title="<?= t('start')?>"        href="start"       class="<?= $task->status == TASK_STATUS_STARTED  ? 'hidden':'symbol'?>"></a>
				<a title="<?= t('complete')?>"     href="complete"    class="<?= $task->status == TASK_STATUS_COMPLETE ? 'hidden':'symbol'?>"></a>
				<a title="<?= t('cancel')?>"       href="cancel"      class="<?= $task->status == TASK_STATUS_CANCELED ? 'hidden':'symbol'?>"></a>
				<a title="<?= t('open')?>"         href="open"        class="<?= $task->status == TASK_STATUS_OPEN     ? 'hidden':'symbol'?>"></a>
				<a title="<?= t('wait')?>"         href="wait"        class="<?= $task->status == TASK_STATUS_PENDING  ? 'hidden':'symbol'?>"></a>
				<a title="<?= t('delete')?>"       href="delete"      class="symbol"></a>
				<a title="<?= t('export task') ?>" href="export"      class="symbol" ></a>
				<a title="<?= t('convert to project'); ?>" href="convert" class="symbol"></a>
				<?php if (isset($services['time'])) { ?>
				<a class="symbol" title="<?= t('add to timetrack')?>" href="<?= getUrl('time','add_task?tid='.$task_id); ?>"></a>
				<?php } ?>
			</span>
			<?php } ?>
		</td>
	</tr>
	<tr>
		<th><?= t('Project')?></th>
		<td class="project">
			<a href="<?= getUrl('project',$task->project_id.'/view'); ?>"><?= $task->project('name')?></a>
			&nbsp;&nbsp;&nbsp;&nbsp;
			<a href="<?= getUrl('files').'?path=project/'.$task->project_id ?>" class="symbol" title="<?= t('show project files'); ?>" target="_blank"></a>
			</td>
	</tr>
	<?php if ($parent = $task->parent()) { ?>
	<tr>
		<th><?= t('Parent')?></th>
		<td><a href="../<?= $parent->id ?>/view"><?= $parent->name; ?></a></td>
	</tr>
	<?php }?>
	<?php if ($task->description){ ?>
	<tr>
		<th><?= t('Description')?></th>
		<td class="description"><?= $task->description(); ?></td>
	</tr>
	<?php } ?>
	<?php if (
			(!empty($task->est_time)) || (!empty($task->child_time()))){ ?>
	<tr>
		<th><?= t('Estimated time')?></th>
		<td>
			<?php if (!empty($task->est_time)){ ?>
			<?= t('? hours',$task->est_time)?>
			<br/>
			<?php } ?>
			<?php if (!empty($task->child_time())){ ?>
			<?= t('Sub-tasks: ? hours',$task->child_time())?>
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
		<th><?= t('Child tasks')?></th>
		<td class="children">
			<?php if (!$show_closed_children) {?>
			<a href="?closed=show"><?= t('show closed child tasks'); ?></a>
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
						($task->users[$user->id]['permissions'] == TASK_PERMISSION_OWNER || $uid == $user->id) // only owner of task may remove other users ; user may remove himself/herself from task
						&& $u['permissions'] != TASK_PERMISSION_OWNER){ // but only if user to be removed is not owner ?>
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
<?php if (isset($services['notes'])) echo request('notes','html',['uri'=>'task:'.$task_id],false,NO_CONVERSION);
} // if task
include '../common_templates/closure.php'; ?>
