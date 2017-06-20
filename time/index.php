<?php $title = 'Umbrella Timetracking';

include '../bootstrap.php';
include 'controller.php';

require_login();
$times = get_time_list(param('order'));
//debug($times,true);

include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<table>
	<tr>
		<th><a href="?order=subject">Subject</a></th>
		<th><a href="?order=subject">Description</a></th>
		<th><a href="?order=start_time">Start</a></th>
		<th><a href="?order=end_time">End</a></th>
		<th>Actions</th>
	</tr>
	
<?php foreach ($times as $id => $time): ?>
	<tr>
		<td><a href="<?= $id ?>/view"><?= $time['subject'] ?></a></td>
		<td><?= $time['description'] ?></td>
		<td><?= $time['start_time']?date('Y-m-d H:i',$time['start_time']):''; ?></td>
		<td><?= $time['end_time']?date('Y-m-d H:i',$time['end_time']):'<a href="'.$id.'/stop">Stop</a>'; ?></td>
		<td>
			<?php if ($time['end_time']) { ?>
			<a href="<?= $id ?>/edit">Edit</a>
			<?php } else { ?>
			<a href="<?= $id ?>/drop">Drop</a>
			<?php } ?>
			<a href="<?= $id ?>/add_subtime">Add subtime</a>
			<a href="<?= $id ?>/complete?returnto=..">Complete</a>
			<a href="<?= $id ?>/cancel?returnto=..">Cancel</a>
		</td>
	</tr>
<?php endforeach; ?>

</table>
<?php include '../common_templates/closure.php'; ?>
