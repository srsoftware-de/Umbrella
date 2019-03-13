<?php include 'controller.php';

require_login('poll');

$poll_id = param('id');
if (empty($poll_id)) {
	error('No poll id provided!');
	redirect(getUrl('poll'));
}

$poll = Poll::load(['ids'=>$poll_id]);
if (empty($poll)){
	error('You are not allowed to modify this poll!');
	redirect(getUrl('poll'));
}

$sums = [];

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<fieldset>
	<legend><?= t('Evaluation of "?"',$poll->name)?></legend>
	<a class="button" href="<?= getUrl('poll','options?id='.$poll->id) ?>"><?= t('Edit options')?></a>
	<a target="_blank" href="<?= getUrl('poll','view?id='.$poll->id)?>"><?= getUrl('poll','view?id='.$poll->id)?></a>
	<table>
		<tr>
			<th><?= t('User')?> / <?= t('Options')?></th>
			<?php foreach ($poll->options() as $oid => $option) { $sums[$oid] = 0; ?>
			<th><?= $option['name'] ?></th>
			<?php } ?>
		</tr>
		<?php foreach ($poll->selections() as $user => $selections) { ?>
		<tr>
			<td><?= $user ?></td>
			<?php foreach ($poll->options() as $oid => $dummy) { $sums[$oid] += $selections[$oid]?>
			<td><?= $selections[$oid] ?></td>
			<?php }?>
		</tr>
		<?php }?>
		<tr>
			<th><?= t('Average:') ?></th>
			<?php foreach ($poll->options() as $oid => $dummy) { ?>
			<th><?= $sums[$oid]/count($poll->selections()) ?></th>
			<?php } ?>
		</tr>
	</table>
</fieldset>

<?php include '../common_templates/closure.php';