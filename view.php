<?php include 'controller.php';

require_login('time');

$time_id = param('id');
if (!$time_id) error('No time id passed to view!');

$time = Timetrack::load(['ids'=>$time_id]);
$title = $time->subject.' - Umbrella';
$documents = isset($services['document']) ? request('document','json',['times'=>$time_id]) : null;

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php';
?>
<h1><?= $time->subject ?></h1>
<table class="vertical time">
	<tr>
		<th><?= t('Time')?></th>
		<td>
			<span class="right">
				<a title="<?= t('edit')?>" href="edit" class="symbol"></a>
				<?php if (!$time->end_time) { ?>
				<a title="<?= t('stop')?>" href="stop">stop</a>
				<?php } ?>
				<a title="<?= t('drop')?>" href="drop" class="symbol"></a> 
			</span>
			<h2>
			<?= date('Y-m-d H:i',$time->start_time); ?>
			<?php if ($time->end_time) { ?>
			... <?= date('Y-m-d H:i',$time->end_time);?> (<?= t('? hours',round(($time->end_time-$time->start_time)/3600,2)) ?>)
			<?php } else { ?>
			(<?= t('started'); ?>)
			<?php } ?>
			</h2>
		</td>
	</tr>
	<tr>
		<th><?= t('Description')?></th><td><?= $parsedown->parse($time->description); ?></td>
	</tr>
	<tr>
		<th>
			<?= t('State')?></th><td><?= t($time->state()); ?>
			<?php if ($time->end_time) { ?>
			<span class="change_state">&rarr;
				<a href="update_state?state=open&returnTo=<?= location('*') ?>"><?= t('open')?></a> | 
				<a href="update_state?state=pending&returnTo=<?= location('*') ?>"><?= t('pending')?></a> |
				<a href="update_state?state=complete&returnTo=<?= location('*') ?>"><?= t('completed')?></a>
			</span>
			<?php } ?>
		</td>
	</tr>
	<?php if (!empty($time->tasks())) {?>
	<tr>
		<th><?= t('Tasks')?></th>
		<td class="tasks">
			<ul>
			<?php foreach ($time->tasks() as $tid => $task) { ?>
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
				<li><a href="<?= getUrl('document', $did.'/view'); ?>"><?= $document['number'] ?></a></li>
			<?php } ?>
			</ul>
		</td>
	</tr>
	<?php } ?>
</table>
<?php include '../common_templates/closure.php'; ?>
