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

$option_id = param('remove_option');
if (!empty($option_id)) {
	$poll->remove_option($option_id);
	redirect(getUrl('poll','options?id='.$poll->id));
}

$option_id = param('disable_option');
if (!empty($option_id)){
	$poll->set_option_status($option_id,Option::DISABLED);
	redirect(getUrl('poll','options?id='.$poll->id));
}

$option_id = param('enable_option');
if (!empty($option_id)){
	$poll->set_option_status($option_id,Option::ENABLED);
	redirect(getUrl('poll','options?id='.$poll->id));
}

$option_id = param('hide_option');
if (!empty($option_id)){
	$poll->set_option_status($option_id,Option::HIDDEN);
	redirect(getUrl('poll','options?id='.$poll->id));
}

$remove_weight = param('remove_weight');
if ($remove_weight !== null) {
	$poll->remove_weight($remove_weight);
	redirect(getUrl('poll','options?id='.$poll->id));
}

$name = param('name');
if (!empty($name)) {
	$option = new Option();
	$option->patch($_POST)->patch(['poll_id'=>$poll_id,'status'=>Option::ENABLED])->save();
	redirect(getUrl('poll','options?id='.$poll->id));
}

$weight = param('weight');
if ($weight !== null) {
	$poll->add_weight($_POST);
	redirect(getUrl('poll','options?id='.$poll->id));
}

$base_url = getUrl('poll');

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<fieldset>
	<legend><?= t('Poll "◊"',$poll->name)?> <span class="symbol"><a href="<?= $base_url.'edit?id='.$poll_id ?>"></a></span></legend>
	<?= markdown($poll->description)?>
	<form method="POST">
		<fieldset>
			<legend><?= t('Options')?></legend>
			<table>
				<tr>
					<th><?= t('Number')?></th>
					<th><?= t('Name')?></th>
					<th><?= t('Description')?></th>
					<th><?= t('Actions')?></th>
				</tr>
				<?php foreach ($poll->options() as $opt_id => $option) { ?>
				<tr>
					<td><?= $opt_id ?></td>
					<td><?= $option->name?></td>
					<td><?= markdown($option->description)?></td>
					<td class="poll_status">
						<a class="button" href="<?= $base_url.'options?id='.$poll->id.'&remove_option='.$opt_id ?>"><?= t('remove') ?></a>
						<?php if ($option->status != Option::ENABLED) { ?>
						<a class="button" href="<?= $base_url.'options?id='.$poll->id.'&enable_option='.$opt_id ?>"><?= t('enable')?></a>
						<?php }
						if ($option->status != Option::DISABLED) { ?>
						<a class="button" href="<?= $base_url.'options?id='.$poll->id.'&disable_option='.$opt_id ?>"><?= t('disable')?></a>
						<?php }
						if ($option->status != Option::HIDDEN) { ?>
						<a class="button" href="<?= $base_url.'options?id='.$poll->id.'&hide_option='.$opt_id ?>"><?= t('hide')?></a>
						<?php } ?>
						<a class="button" href="<?= $base_url.'edit_option?poll='.$poll->id.'&option='.$opt_id ?>"><?= t('edit')?></a>
					</td>
				</tr>
				<?php } ?>
				<tr>
					<td><?= 1+$opt_id ?></td>
					<td>
						<input type="text" name="name" value="<?= param('name') ?>" autofocus="autofocus">
					</td>
					<td>
						<textarea name="description"><?= trim(param('description'))?></textarea>
					</td>
					<td>
						<button type="submit"><?= t('add')?></button>
					</td>
				</tr>
			</table>
		</fieldset>
	</form>

	<form method="POST">
		<fieldset>
			<legend><?= t('Weights')?></legend>
			<table>
				<tr>
					<th><?= t('Weight (Number!)')?></th>
					<th><?= t('Description')?></th>
					<th><?= t('Actions')?></th>
				</tr>
				<?php foreach ($poll->weights() as $weight => $meta) { ?>
				<tr>
					<td><?= $weight ?></td>
					<td><?= markdown($meta['description'])?></td>
					<td><a class="button" href="<?= getUrl('poll','options?id='.$poll->id.'&remove_weight='.$weight)?>"><?= t('remove') ?></a></td>
				</tr>
				<?php } ?>
				<tr>
					<td>
						<input type="number" name="weight" value="<?= param('weight',1) ?>">
					</td>
					<td>
						<textarea name="description"><?= trim(param('description'))?></textarea>
					</td>
					<td>
						<button type="submit"><?= t('add')?></button>
					</td>
				</tr>
			</table>
		</fieldset>
	</form>
	<fieldset>
		<legend><?= t('Sharable link')?></legend>
		<?= t('You can send this link to other users which you want to invite to this poll.')?>
		<p>
			<a target="_blank" href="<?= getUrl('poll','view?id='.$poll->id)?>"><?= getUrl('poll','view?id='.$poll->id)?></a>
		</p>
		<a class="button" href="<?= getUrl('poll','evaluate?id='.$poll->id)?>"><?= t('Evaluation')?></a>
	</fieldset>
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
