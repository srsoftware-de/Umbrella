<?php 

include '../bootstrap.php';
include 'controller.php';

require_login();
$time_id = param('id');
if (!$time_id) error('No time id passed to view!');

$time = load_time($time_id);
load_tasks($time);

$title = $time['subject'].' - Umbrella';
include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php';
?>
<h1><?= $time['subject'] ?></h1>
<table class="vertical">
	<tr>
		<th>Time</th>
		<td>
			<span class="right">
				<a title="<?= t('edit')?>" href="edit" class="symbol"></a>
				<a title="<?= t('stop')?>" href="stop">stop</a> 
			</span>
			<h2>
			<?= date('Y-m-d H:i',$time['start_time']); ?>
			<?php if ($time['end_time']) { ?>
			... <?= date('Y-m-d H:i',$time['end_time']);?> (<?= ($time['end_time']-$time['start_time'])/3600 ?> hours)
			<?php } else { ?>
			(open)
			<?php } ?>
			</h2>
		</td>
	</tr>
	<tr>
		<th>Description</th><td><?= $time['description']; ?></td>
	</tr>
	<?php if (!empty($time['tasks'])) {?>
	<tr>
		<th>Tasks</th>
		<td class="tasks">
			<ul>
			<?php foreach ($time['tasks'] as $tid => $task) { ?>
				<li <?= $task['status']>=40?'class="pending"':'' ?>><a href="<?= getUrl('task', $tid.'/view'); ?>"><?= $task['name'] ?></a></li>
			<?php } ?>
			</ul>
		</td>
	</tr>
	<?php } ?>
</table>
<?php include '../common_templates/closure.php'; ?>
