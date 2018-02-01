<?php 

include '../bootstrap.php';
include 'controller.php';

require_login('time');
$time_id = param('id');
if (!$time_id) error('No time id passed to view!');

$time = load_times(['ids'=>$time_id,'single'=>true]);
if (isset($time['task_ids'])) $time['tasks'] = request('task','json',['ids'=>$time['task_ids']]);

if (file_exists('../lib/parsedown/Parsedown.php')){
	include '../lib/parsedown/Parsedown.php';
	$time['description'] = Parsedown::instance()->parse($time['description']);
} else {
	$time['description'] = str_replace("\n", "<br/>", $time['description']);
}

$title = $time['subject'].' - Umbrella';
$documents = isset($services['invoice']) ? request('invoice','json',['times'=>$time_id]) : null;

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php';
?>
<h1><?= $time['subject'] ?></h1>
<table class="vertical time">
	<tr>
		<th><?= t('Time')?></th>
		<td>
			<span class="right">
				<a title="<?= t('edit')?>" href="edit" class="symbol"></a>
				<?php if (!$time['end_time']) { ?>
				<a title="<?= t('stop')?>" href="stop">stop</a>
				<?php } ?>
				<a title="<?= t('drop')?>" href="drop" class="symbol"></a> 
			</span>
			<h2>
			<?= date('Y-m-d H:i',$time['start_time']); ?>
			<?php if ($time['end_time']) { ?>
			... <?= date('Y-m-d H:i',$time['end_time']);?> (<?= t('? hours',round(($time['end_time']-$time['start_time'])/3600,2)) ?>)
			<?php } else { ?>
			(open)
			<?php } ?>
			</h2>
		</td>
	</tr>
	<tr>
		<th><?= t('Description')?></th><td><?= $time['description']; ?></td>
	</tr>
	<tr>
		<th>
			<?= t('State')?></th><td><?= t(state_text($time['state'])); ?>
			<?php if ($time['end_time']) { ?>
			<span class="change_state">&rarr;
				<a href="update_state?OPEN=2&returnTo=<?= location('*') ?>"><?= t('open')?></a> | 
				<a href="update_state?PENDING=2&returnTo=<?= location('*') ?>"><?= t('pending')?></a> |
				<a href="update_state?COMPLETED=2&returnTo=<?= location('*') ?>"><?= t('completed')?></a>
			</span>
			<?php } ?>
		</td>
	</tr>
	<?php if (!empty($time['tasks'])) {?>
	<tr>
		<th><?= t('Tasks')?></th>
		<td class="tasks">
			<ul>
			<?php foreach ($time['tasks'] as $tid => $task) { ?>
				<li <?= $task['status']>=40?'class="pending"':'' ?>><a href="<?= getUrl('task', $tid.'/view'); ?>"><?= $task['name'] ?></a></li>
			<?php } ?>
			</ul>
		</td>
	</tr>
	<?php } ?>
	<?php if ($documents) { ?>
		<tr>
		<th><?= t('References')?></th>
		<td class="documents">
			<ul>
			<?php foreach ($documents as $did => $document) { ?>
				<li><a href="<?= getUrl('invoice', $did.'/view'); ?>"><?= $document['number'] ?></a></li>
			<?php } ?>
			</ul>
		</td>
	</tr>
	<?php } ?>
</table>
<?php include '../common_templates/closure.php'; ?>
