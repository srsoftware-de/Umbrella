<?php include 'controller.php';

require_login('time');

$options = ['order'=>param('order')];
$project_id = param('project');
if ($project_id) $options['project_id'] = [$project_id];
$times = Timetrack::load($options);

$show_complete = param('complete') == 'show';

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php';

if ($show_complete){ ?>
<a class="symbol" title="<?= t('export times')?>" href="export?complete=show"></a>
<?php } else { ?>
<a class="symbol" title="<?= t('show completed times') ?>" href="?complete=show"></a>
<a class="symbol" title="<?= t('export times to CSV')?>" href="export<?= $project_id ? '?project='.$project_id:''?>"></a>
<?php } ?>

<table>
	<tr>
		<th><?= t('Projects')?></th>
		<th><a href="?order=subject"><?= t('Subject')?></a></th>
		<th><a href="?order=description"><?= t('Description')?></a></th>
		<th><a href="?order=start_time"><?= t('Start')?></a></th>
		<th><a href="?order=end_time"><?= t('End')?></a></th>
		<th><?= t('Hours')?></th>
		<th><a href="?order=state"><?= t('State')?></a></th>
		<th><?= t('Actions')?></th>
	</tr>

<?php $sum = 0; foreach ($times as $id => $time){
	if (!$show_complete && $time->state == TIME_STATUS_COMPLETE) continue;
	$time_projects=[];
	foreach ($time->tasks() as $task) $time_projects[$task['project_id']] = $task['project']['name'];
	$duration = 0;
	if ($time->end_time) $duration = $time->end_time-$time->start_time;
	if (in_array($time->state(),['open','pending'])) $sum+=$duration;
	?>
	<tr class="project<?= implode(' project',array_keys($time_projects))?>">
		<td>
		<?php foreach ($time_projects as $pid => $name){?>
			<span class="hover_h">
				<a href="<?= getUrl('project',$pid.'/view') ?>"><?= $name ?></a>&nbsp;<a href="<?= getUrl('time','?project='.$pid)?>" class="symbol" ></a>
			</span>
		<?php }?>
		</td>
		<td><a href="<?= $id ?>/view"><?= $time->subject ?></a></td>
		<td><?= $parsedown->parse($time->description) ?></td>
		<td><a href="<?= $id ?>/view"><?= $time->start_time?date('Y-m-d H:i',$time->start_time):''; ?></a></td>
		<td><a href="<?= $id ?>/view"><?= $time->end_time?date('Y-m-d H:i',$time->end_time):'<a href="'.$id.'/stop">Stop</a>'; ?></a></td>
		<td><a href="<?= $id ?>/view"><?= $time->end_time?round($duration/3600,2):'' ?></a></td>
		<td><a href="<?= $id ?>/edit?return_to=<?= location() ?>"><?= t($time->state()) ?></a></td>
		<td>
			<?php if ($time->end_time) { ?>
			<a class="symbol" title="<?= t('edit') ?>" href="<?= $id ?>/edit?return_to=<?= location() ?>"></a>
			<?php } ?>
			<a class="symbol" title="<?= t('drop') ?>" href="<?= $id ?>/drop"></a>
			<a class="symbol" title="<?= t('complete') ?>" href="<?= $id ?>/complete"></a>
		</td>
	</tr>
<?php }
if ($project_id){ ?>
	<tr>
		<td colspan="4"></td>
		<td><?= t('Sum:') ?></td>
		<td><?= round($sum/3600,2) ?></th>
		<td><?= t('open')?></td>
		<td></td>
	</tr>
<?php } // if project_id ?>


</table>
<?php include '../common_templates/closure.php'; ?>
