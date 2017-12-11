<?php $title = 'Umbrella Timetracking';

include '../bootstrap.php';
include 'controller.php';

require_login('time');
$times = get_time_list(param('order'));
//debug($times,true);

include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<table>
	<tr>
		<th><a href="?order=subject"><?= t('Subject')?></a></th>
		<th><a href="?order=subject"><?= t('Description')?></a></th>
		<th><a href="?order=start_time"><?= t('Start')?></a></th>
		<th><a href="?order=end_time"><?= t('End')?></a></th>
		<th><?= t('Hours')?></th>
		<th><?= t('State')?></th>
		<th><?= t('Actions')?></th>
	</tr>
	
<?php foreach ($times as $id => $time): ?>
	<tr>
		<td><a href="<?= $id ?>/view"><?= $time['subject'] ?></a></td>
		<td><?= $time['description'] ?></td>
		<td><?= $time['start_time']?date('Y-m-d H:i',$time['start_time']):''; ?></td>
		<td><?= $time['end_time']?date('Y-m-d H:i',$time['end_time']):'<a href="'.$id.'/stop">Stop</a>'; ?></td>
		<td><?= $time['end_time']?(($time['end_time']-$time['start_time'])/3600).' hours':'' ?></td>
		<td><?= TIME_STATES[$time['state']] ?></td>
		<td>
			<?php if ($time['end_time']) { ?>
			<a class="symbol" title="edit" href="<?= $id ?>/edit"></a>
			<?php } ?>
			<a class="symbol" title="drop" href="<?= $id ?>/drop">	</a>
		</td>
	</tr>
<?php endforeach; ?>

</table>
<?php include '../common_templates/closure.php'; ?>
