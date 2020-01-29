<?php include 'controller.php';

require_login('poll');

$base_url = getUrl('poll');

$poll_id = param('id');
if (empty($poll_id)) {
	error('No poll id provided!');
	redirect($base_url);
}

$poll = Poll::load(['ids'=>$poll_id]);
if (empty($poll)){
	error('You are not allowed to modify this poll!');
	redirect($base_url);
}

$users = request('user','json');

$options    = $poll->options();

$remove_votes = param('remove_votes');
if ($remove_votes && param('confirm')=='yes') {
	$poll->remove_votes_of($remove_votes);
	redirect($base_url.'evaluate?id='.$poll_id);
}

$selections = $poll->selections();

$sums = [];

foreach ($options as $option_id => $option){
	$sum = 0;
	foreach ($selections as $user_id => $selection) $sum += $selection[$option_id];
	if (!isset($sums[$sum])) $sums[$sum] = [];
	$sums[$sum][] = $option_id;
}

ksort($sums);

// at this point, sums is a map of sums to option ids:
// [sums] => Array(
// 	[-10] => Array(
// 			[0] => 16
// 		)
// 	[0] => Array(
// 			[0] => 22
// 		)
// 	[5] => Array(
// 			[0] => 12
// 			)

// 	[10] => Array(
// 			[0] => 11
// 		)
// 	[15] => Array(
// 			[0] => 1
// 			[1] => 3
// 			[2] => 4
// 			[3] => 7
// 			[4] => 8
// 			[5] => 21
// 		)
// 	[20] => Array(
// 			[0] => 2
// 			[1] => 6
// 			[2] => 10
// 			[3] => 17
// 			[4] => 20
// 		)
// 	[25] => Array(
// 			[0] => 9
// 			[1] => 13
// 			[2] => 14
// 			[3] => 18
// 		)
// 	[30] => Array(
// 			[0] => 5
// 			[1] => 15
// 			[2] => 19
// 		)
// )


$vertical = count($options) < count($selections);

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<?php if ($remove_votes) { ?>
<fieldset>
	<legend><?= t('Really remove votes of "◊"?',is_numeric($remove_votes)?$users[$remove_votes]['login']:$remove_votes); ?></legend>
	<a class="button" href="<?= $base_url.'evaluate?id='.$poll_id.'&remove_votes='.$remove_votes.'&confirm=yes'?>"><?= t('yes')?></a>
	<a class="button" href="<?= $base_url.'evaluate?id='.$poll_id?>"><?= t('no')?></a>
</fieldset>
<?php }?>

<fieldset>
	<legend><?= t('Evaluation of "◊"',$poll->name)?></legend>
	<a class="button" href="<?= getUrl('poll','options?id='.$poll->id) ?>"><?= t('Edit options')?></a>
	<a class="button" href="<?= getUrl('poll','view?id='.$poll->id)?>" target="_blank"><?= t('Visit poll') ?></a>
	<?php if ($vertical) { ?>
	<table>
		<tr>
			<th><?= t('User')?> / <?= t('Options')?></th>
			<?php foreach ($sums as $sum => $option_list){
				foreach ($option_list as $option_id){ ?>
			<th><?= $options[$option_id]->name ?></th><?php
				} // foreach $option_lost as $option_id
			} // foreach $sums as $ums => $option_list ?>
		</tr>
		<?php foreach ($selections as $user_id => $user_selections) { ?>
		<tr>
			<td><?= is_numeric($user_id)?'<a target="_blank" href="'.getUrl('user',$user_id.'/view').'">'.$users[$user_id]['login'].'</a>':$user_id ?></td>
			<?php foreach ($sums as $sum => $option_list){
				foreach ($option_list as $option_id){ ?>
			<td><?= $user_selections[$option_id] ?></td><?php
					} // foreach $option_lost as $option_id
				} // foreach $sums as $ums => $option_list ?>
		</tr><?php } // foreach selections as user_id => user_selections ?>
		<tr>
			<th><?= t('Average:')?></th>
			<?php foreach ($sums as $sum => $option_list){
				foreach ($option_list as $option_id){ ?>
			<th><?= $sum/count($selections)?></th><?php
				} // foreach $option_lost as $option_id
			} // foreach $sums as $ums => $option_list ?>
		</tr>
	</table>
	<?php } else { ?>
	<table>
		<tr>
			<th><?= t('Options')?> / <?= t('User')?></th>
			<?php foreach ($selections as $uśer_id => $user_selections) { ?>
			<th><?= is_numeric($uśer_id)?'<a target="_blank" href="'.getUrl('user',$uśer_id.'/view').'">'.$users[$uśer_id]['login'].'</a>':$uśer_id ?> <a class="symbol" href="<?= $base_url ?>evaluate?id=<?= $poll_id ?>&remove_votes=<?= $uśer_id ?>"></a></th>
			<?php } ?>
			<th><?= t('Average:')?></th>
		</tr>
		<?php foreach ($sums as $sum => $option_list) { ?>
		<?php foreach ($option_list as $option_id) {
			$class = null;
			switch ($options[$option_id]->status){
				case Option::HIDDEN:
					$class = 'hidden'; break;
				case Option::DISABLED:
					$class = 'disabled'; break;
			}
		?>
		<tr<?= $class?' class="'.$class.'"':''?>>
			<th><?= $options[$option_id]->name ?></th>
			<?php foreach ($selections as $user_selections) { ?>
			<td><?= $user_selections[$option_id]?></td>
			<?php } // foreach selections as user_id => user_selections ?>
			<td><?= $sum/count($selections) ?></td>
		</tr>
		<?php } // foreach $option_list as $option_id ?>
		<?php } // foreach sums as sum => $option_list ?>
	</table>
	<?php } ?>
</fieldset>

<?php if (isset($services['notes'])) {
	$notes = request('notes','html',['uri'=>'poll:'.$poll->id],false,NO_CONVERSION);
	if ($notes){ ?>
	<fieldset>
		<legend><?= t('Notes')?></legend>
		<?= $notes ?>
	</fieldset>
<?php }}

include '../common_templates/closure.php';