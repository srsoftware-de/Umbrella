<?php include 'controller.php';

require_login('time');

if ($key = param('key')){
	$times = Timetrack::load(['search'=>$key]);
	$url = getUrl('time');
	if (!empty($times)){ ?>

<table>
	<tr>
		<th><?= t('Subject')?></th>
		<th><?= t('Description')?></th>
		<th><?= t('Start')?></th>
		<th><?= t('End')?></th>
		<th><?= t('Hours')?></th>
		<th><?= t('State')?></th>
		<th><?= t('Actions')?></th>
	</tr>

<?php foreach ($times as $id => $time){ ?>
	<tr>
		<td><a href="<?= $url.$id ?>/view"><?= emphasize($time->subject,$key) ?></a></td>
		<td><a href="<?= $url.$id ?>/view"><?= emphasize($time->description,$key) ?></a></td>
		<td><a href="<?= $url.$id ?>/view"><?= emphasize($time->start_time?date('Y-m-d H:i',$time->start_time):'',$key); ?></a></td>
		<td><a href="<?= $url.$id ?>/view"><?= emphasize($time->end_time?date('Y-m-d H:i',$time->end_time):'<a href="'.$id.'/stop">Stop</a>',$key); ?></a></td>
		<td><a href="<?= $url.$id ?>/view"><?= emphasize($time->end_time?round(($time->end_time-$time->start_time)/3600,2):'',$key) ?></a></td>
		<td><a href="<?= $url.$id ?>/edit"><?= emphasize(t($time->state()),$key) ?></a></td>
		<td>
			<?php if ($time->end_time) { ?>
			<a class="symbol" title="<?= t('edit') ?>" href="<?= $url.$id ?>/edit"></a>
			<?php } ?>
			<a class="symbol" title="<?= t('drop') ?>" href="<?= $url.$id ?>/drop"></a>
		</td>
	</tr>
<?php } ?>

</table>
	<?php }
}