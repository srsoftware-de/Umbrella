<?php include 'controller.php';
require_login('task');

$bookmark = false;

if ($task_id = param('id')){
	if ($task = Task::load(['ids'=>$task_id])){
		$title = $task->name.' - Umbrella';
		$show_closed_children = param('closed') == 'show';

		if ($note_id = param('note_added')) send_note_notification($task,$note_id);

		if (isset($services['bookmark'])){
			$hash = sha1(location('*'));
			$bookmark = request('bookmark','json_get?id='.$hash);
		}
//		header('Content-Disposition: attachment; filename="'.$task->name.'.html"');
	} else error('Task does not exist or you are not allowed to access it.');
} else error('No task id passed to view!');

function display_children($task){
	global $show_closed_children,$task_id,$services;
	if (empty($task->children())) return; ?>
	<ul>
	<?php foreach ($task->children() as $id => $child_task) { if (!$show_closed_children && $child_task->status >= 60) continue;	?>
		<li class="<?= task_state($child_task->status) ?>">
			<h2><a title="<?= t('view')?>"		href="../<?= $id ?>/view"><?= $child_task->name ?></a></h2>
			<?php if (!empty($child_task->description)) { ?>
			<fieldset>
				<legend><?= t('Description')?></legend>
				<?= $child_task->description()?>
			</fieldset>
			<?php } // description not empty ?>
			<?php if (isset($services['notes'])) echo request('notes','html',['uri'=>'task:'.$child_task->id,'form'=>false],false,NO_CONVERSION); ?>
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
			<h1><?= $task->name ?></h1>
		</td>
	</tr>
	<tr>
		<th><?= t('Project')?></th>
		<td class="project">
			<a href="<?= getUrl('project',$task->project_id.'/view'); ?>"><?= $task->project('name')?></a>
		</td>
	</tr>
	<?php if ($task->parent_task_id) { ?>
	<tr>
		<th><?= t('Parent')?></th>
		<td><a href="../<?= $task->parent_task_id ?>/view"><?= $task->parent('name');?></a></td>
	</tr>
	<?php }?>
	<?php if ($task->description()){ ?>
	<tr>
		<th><?= t('Description')?></th>
		<td class="description"><?= $task->description(); ?></td>
	</tr>
	<?php } ?>
	<?php if (!empty($task->est_time) || !empty($task->child_time())){ ?>
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
	<?php if ($task->start_date) { ?>
	<tr>
		<th><?= t('Start date')?></th>
		<td><?= $task->start_date ?></td>
	</tr>
	<?php } ?>
	<?php if ($task->due_date) { ?>
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
