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

$name = param('name');
if (!empty($name)) $poll->add_option($_POST);

$weight = param('weight');
if ($weight !== null) $poll->add_weight($_POST);

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<fieldset>
	<legend><?= t('Poll "?"',$poll->name)?></legend>
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
					<td><?= $option['name']?></td>
					<td><?= markdown($option['description'])?></td>
					<td></td>
				</tr>
				<?php } ?>
				<tr>
					<td><?= 1+$opt_id ?></td>
					<td>
						<input type="text" name="name" value="<?= param('name') ?>">
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
					<td></td>
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
</fieldset>

<?php include '../common_templates/closure.php';