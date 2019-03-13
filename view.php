<?php include 'controller.php';

$poll_id = param('id');
if (empty($poll_id)) {
	error('No poll id provided!');
	redirect(getUrl('poll'));
}

$poll = Poll::load(['ids'=>$poll_id,'open'=>true]);
if (empty($poll)){
	error('You are not allowed to view this poll!');
	redirect(getUrl('poll'));
}

$poll->options();
$poll->weights();

$confirmed = true;
$options = param('option');
$user = param('user');
if (!empty($options)) {
	if (empty($user)){
		error('You must provide a name!');
	} else {
		$selections = $poll->get_selections($user);
		if (!empty($selections)) $confirmed = false;
		if (param('confirm')=='on') $confirmed = true;
		if ($confirmed) {
			$poll->save_selection($_POST);
			info('Your selection has been saved.');
		} else {
			error('A user with name "?" has already submitted selections. Enter another name or confirm to overwrite existing selections!',$user);
		}
	}
}

include '../common_templates/head.php'; ?>

<fieldset>
	<legend><?= t('Poll "?"',$poll->name) ?></legend>
	<?= markdown($poll->description)?>
	<form method="POST">
		<fieldset>
			<legend><?= t('Who are you?')?></legend>
			<?php include '../common_templates/messages.php'; ?>
			<input type="text" name="user" value="<?= param('user') ?>"/>
		</fieldset>
		<fieldset>
			<legend><?= t('Make your choices!')?></legend>
			<table>
				<tr>
					<th></th>
					<th><?= t('Weights')?></th>
				</tr>
				<tr>
					<th><?= t('Option')?></th>
					<?php foreach ($poll->weights() as $weight => $meta) { ?>
					<th class="hover" style="min-width: 40px">
						<?= $weight ?>
						<span class="hidden">
							<?= $meta['description']?>
						</span>
					</th>
					<?php }?>
				</tr>
				<?php foreach ($poll->options() as $oid => $option) { ?>
				<tr class="hover">
					<td>
						<?= $option['name'] ?>
						<span class="hidden">
							<?= markdown($option['description'])?>
						</span>
					</td>
					<?php foreach ($poll->weights() as $weight => $meta) { ?>
					<td>
						<input type="radio" name="option[<?= $oid ?>]" value="<?= $weight ?>" <?= (isset($options[$oid]) && $options[$oid]==$weight)?'checked="checked" ':'' ?>/>
					</td>
					<?php }?>
				</tr>
				<?php } ?>
			</table>
		</fieldset>
		<?php if (!$confirmed){ ?>
		<fieldset>
			<legend><?= t('Confirmation required') ?></legend>
			<?= t('Are you sure you want to overwrite the choices of ??',$user)?>
			<label>
				<input type="checkbox" name="confirm" />
				<?= t('Yes, I am sure.')?>
			</label>
		</fieldset>
		<?php }?>
		<button type="submit"><?= t('Submit')?></button>
	</form>
</fieldset>

<?php include '../common_templates/closure.php';