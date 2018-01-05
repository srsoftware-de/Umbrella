<?php

include '../bootstrap.php';
include 'controller.php';

require_login('task');
$task_id = param('id');
if ($task_id){
	if ($task = load_tasks(['ids'=>$task_id])){
		if ($task['parent_task_id']) $task['parent'] = load_tasks(['ids'=>$task['parent_task_id']]);
		load_children($task,99); // up to 99 levels deep
		load_requirements($task);
	
		$project_users_permissions = request('project','user_list',['id'=>$task['project_id']]); // needed to load project users
		$project_users = request('user','list',['ids'=>implode(',', array_keys($project_users_permissions))]); // needed to load task users
		load_users($task,$project_users);
	
		$title = $task['name'].' - Umbrella';
		$task['project'] = request('project','json',['ids'=>$task['project_id'],'single'=>true]);
		$show_closed_children = param('closed') == 'show';
		
		if (file_exists('../lib/parsedown/Parsedown.php')){
			include '../lib/parsedown/Parsedown.php';
			$task['description'] = Parsedown::instance()->parse($task['description']);
		} else {
			$task['description'] = str_replace("\n", "<br/>", $task['description']);
		}
	
		if (isset($services['bookmark'])){
			$hash = sha1(location('*'));
			$bookmark = request('bookmark','json_get?id='.$hash);
		}	
	} else { // task not loaded
		$title = 'Umbrella Task Management';
		error('Task does not exist or you are not allowed to access it.');
	}
} else /*no task id*/ error('No task id passed to view!');

function display_children($task){
	global $show_closed_children,$task_id,$services;
	if (!isset($task['children'])) return; ?>
	<ul>
	<?php foreach ($task['children'] as $id => $child_task) {
			if (!$show_closed_children && $child_task['status'] >= 60) continue;
		?>
		<li class="<?= task_state($child_task['status']) ?>">
			<a title="<?= t('view')?>"		href="../<?= $id ?>/view"><?= $child_task['name']?></a>
			<span class="hover_h">
			<a title="<?= t('edit')?>"			href="../<?= $id ?>/edit?redirect=../<?= $task_id ?>/view"     class="symbol"></a>
			<a title="<?= t('complete')?>"		href="../<?= $id ?>/complete?redirect=../<?= $task_id ?>/view" class="<?= $child_task['status'] == TASK_STATUS_COMPLETE ? 'hidden':'symbol'?>"></a>
			<a title="<?= t('cancel')?>"		href="../<?= $id ?>/cancel?redirect=../<?= $task_id ?>/view"   class="<?= $child_task['status'] == TASK_STATUS_CANCELED ? 'hidden':'symbol'?>"></a>
			<a title="<?= t('add subtask')?>"	href="../<?= $id ?>/add_subtask" class="symbol"></a>
			<a title="<?= t('start')?>"			href="../<?= $id ?>/start?redirect=../<?= $task_id ?>/view"    class="<?= $child_task['status'] == TASK_STATUS_STARTED  ? 'hidden':'symbol'?>"></a>
			<a title="<?= t('open')?>"			href="../<?= $id ?>/open?redirect=../<?= $task_id ?>/view"     class="<?= $child_task['status'] == TASK_STATUS_OPEN     ? 'hidden':'symbol'?>"></a>
			<a title="<?= t('wait')?>"			href="../<?= $id ?>/wait?redirect=../<?= $task_id ?>/view"	   class="<?= $child_task['status'] == TASK_STATUS_PENDING  ? 'hidden':'symbol'?>"></a>

			<?php if (isset($services['time'])) { ?>
				<a class="symbol" title="<?= t('add to timetrack')?>" href="<?= getUrl('time','add_task?tid='.$task_id); ?>"></a>
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
include '../common_templates/messages.php'; ?>

<?php if ($task) { ?>
<table class="vertical tasks">
	<tr>
		<th><?= t('Task')?></th>
		<td>
			<h1><?= $task['name'] ?></h1>
			<span class="right">
				<a title="<?= t('edit')?>"		href="edit"		class="symbol"></a>
				<a title="<?= t('add subtask')?>" href="add_subtask" class="symbol"></a>
				<a title="<?= t('add user')?>" href="add_user" class="symbol"></a>
				<a title="<?= t('start')?>"    href="start"    class="<?= $task['status'] == TASK_STATUS_STARTED  ? 'hidden':'symbol'?>"></a>
				<a title="<?= t('complete')?>" href="complete" class="<?= $task['status'] == TASK_STATUS_COMPLETE ? 'hidden':'symbol'?>"></a>
				<a title="<?= t('cancel')?>"   href="cancel"   class="<?= $task['status'] == TASK_STATUS_CANCELED ? 'hidden':'symbol'?>"></a>
				<a title="<?= t('open')?>"     href="open"     class="<?= $task['status'] == TASK_STATUS_OPEN     ? 'hidden':'symbol'?>"></a>
				<a title="<?= t('wait')?>"     href="wait"     class="<?= $task['status'] == TASK_STATUS_PENDING  ? 'hidden':'symbol'?>"></a>
				<a title="<?= t('delete')?>"   href="delete"   class="symbol"></a>

				<?php if (isset($services['time'])) { ?>
				<a class="symbol" title="<?= t('add to timetrack')?>" href="<?= getUrl('time','add_task?tid='.$task_id); ?>"></a>
				<?php } ?>
			</span>
						
		</td>
	</tr>
	<tr>
		<th><?= t('Project')?></th>
		<td class="project">
			<a href="<?= getUrl('project',$task['project_id'].'/view'); ?>"><?= $task['project']['name']?></a>
		</td>
	</tr>
	<?php if ($task['parent_task_id']) { ?>
	<tr>
		<th><?= t('Parent')?></th>
		<td><a href="../<?= $task['parent_task_id'] ?>/view"><?= $task['parent']['name'];?></a></td>
	</tr>
	<?php }?>
	<?php if ($task['description']){ ?>
	<tr>
		<th><?= t('Description')?></th>
		<td><?= $task['description']; ?></td>
	</tr>
	<?php } ?>
	<?php if ($task['est_time'] > 0 || $task['est_time_children'] > 0){ ?>
	<tr>
		<th><?= t('Estimated time')?></th>
		<td>
			<?php if ($task['est_time'] > 0){ ?>
			<?= t('? hours',$task['est_time'])?>
			<br/>
			<?php } ?>
			<?php if ($task['est_time_children'] > 0){ ?>
			<?= t('Sub-tasks: ? hours',$task['est_time_children'])?>
			<?php } ?>
		</td>
	</tr>
	<?php } ?>	
	<?php if ($task['start_date']) { ?>
	<tr>
		<th><?= t('Start date')?></th>
		<td><?= $task['start_date'] ?></td>
	</tr>
	<?php } ?>
	<?php if ($task['due_date']) { ?>
	<tr>
		<th><?= t('Due date')?></th>
		<td><?= $task['due_date'] ?></td>
	</tr>
	<?php } ?>
	<?php if (isset($task['requirements'])) { ?>
	<tr>
		<th><?= t('Prerequisites')?></th>
		<td class="requirements">
			<ul>
			<?php foreach ($task['requirements'] as $id => $required_task) {?>
				<li <?= in_array($required_task['status'],array(TASK_STATUS_CANCELED,TASK_STATUS_COMPLETE))?'class="inactive"':''?>><a href="../<?= $id ?>/view"><?= $required_task['name']?></a></li>
			<?php } ?>
			</ul>
		</td>
	</tr>
	<?php } ?>
	<?php if (isset($task['children'])){?>
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
	<?php if (isset($task['users']) && !empty($task['users'])){ ?>
	<tr>
		<th><?= t('Users')?></th>
		<td>
			<ul>
			<?php foreach ($task['users'] as $uid => $u) { ?>
				<li>
					<?= $u['login'] ?>
					(<?= t($TASK_PERMISSIONS[$u['permissions']]) ?>)
					<?php if (
						($task['users'][$user->id]['permissions'] == TASK_PERMISSION_OWNER || $uid == $user->id) 
						&& $u['permissions'] != TASK_PERMISSION_OWNER){ ?>
					<a class="symbol" href="drop_user?uid=<?= $uid ?>"></a>
					<?php } ?>
				</li>
			<?php } ?>
			</ul>
		</td>
	</tr>
	<?php } ?>
	<?php if ($bookmark) { ?>
	<tr>
		<th><?= t('Tags')?></th>
		<td>
		<?php $base_url = getUrl('bookmark');
		foreach ($bookmark['tags'] as $tag){ ?>
			<a class="button" href="<?= $base_url.'/'.$tag.'/view' ?>"><?= $tag ?></a>
		<?php } ?>
		</td>
	</tr>
	<?php } ?>
</table>
<?php if (isset($services['notes'])) echo request('notes','html',['uri'=>'task:'.$task_id],false,NO_CONVERSSION);
} // if task	
include '../common_templates/closure.php'; ?>
