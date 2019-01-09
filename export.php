<?php include 'controller.php';
require_login('task');

$task_id = param('id');
$bookmark = false;
if ($task_id){
	if ($task = load_tasks(['ids'=>$task_id])){
		if ($task['parent_task_id']) $task['parent'] = load_tasks(['ids'=>$task['parent_task_id']]);
		load_children($task,99); // up to 99 levels deep
		load_requirements($task);

		$project_users_permissions = request('project','json',['ids'=>$task['project_id'],'users'=>'only']); // needed to load project users
		$project_users = request('user','json',['ids'=>array_keys($project_users_permissions)]); // needed to load task users
		load_users($task,$project_users);

		$title = $task['name'].' - Umbrella';
		$task['project'] = request('project','json',['ids'=>$task['project_id'],'single'=>true]);
		$show_closed_children = param('closed') == 'show';

		if ($note_id = param('note_added')) send_note_notification($task,$note_id);

		parseDownFormat($task);

		if (isset($services['bookmark'])){
			$hash = sha1(location('*'));
			$bookmark = request('bookmark','json_get?id='.$hash);
		}
		header('Content-Disposition: attachment; filename="'.$task['name'].'.html"');
	} else { // task not loaded
		$title = 'Umbrella Task Management';
		error('Task does not exist or you are not allowed to access it.');
	}
} else /*no task id*/ error('No task id passed to view!');

$write_access = write_access($task);

function display_children($task){
	global $show_closed_children,$task_id,$services;
	if (!isset($task['children'])) return; ?>
	<ul>
	<?php foreach ($task['children'] as $id => $child_task) {
			if (!$show_closed_children && $child_task['status'] >= 60) continue;
			parseDownFormat($child_task);
		?>
		<li class="<?= task_state($child_task['status']) ?>">
			<h1><a title="<?= t('view')?>"		href="../<?= $id ?>/view"><?= $child_task['name']?></a></h1>
			<fieldset>
				<legend><?= t('Descriptions')?></legend>
				<?= $child_task['description']?>
			</fieldset>
			<?php if (isset($services['notes'])) echo request('notes','html',['uri'=>'task:'.$task['id'],'form'=>false],false,NO_CONVERSION); ?>
			<?php display_children($child_task);?>
		</li>
	<?php }?>
	</ul>
	<?php
}

include '../common_templates/head.php'; ?>

<?php if ($task) { ?>
<table class="vertical tasks">
	<tr>
		<th><?= t('Task')?></th>
		<td>
			<h1><?= $task['name'] ?></h1>
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
		<td class="description"><?= $task['description']; ?></td>
	</tr>
	<?php } ?>
	<?php if (
		(isset($task['est_time']) && $task['est_time'] > 0) ||
		(isset($task['est_time_children']) && $task['est_time_children'] > 0)
		){ ?>
	<tr>
		<th><?= t('Estimated time')?></th>
		<td>
			<?php if ($task['est_time'] > 0){ ?>
			<?= t('? hours',$task['est_time'])?>
			<br/>
			<?php } ?>
			<?php if (isset($task['est_time_children']) && $task['est_time_children'] > 0){ ?>
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
			<?php display_children($task); ?>
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
<?php if (isset($services['notes'])) echo request('notes','html',['uri'=>'task:'.$task_id,'form'=>false],false,NO_CONVERSION);
} // if task
include '../common_templates/closure.php'; ?>
