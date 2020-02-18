<?php include 'controller.php';

// discover, if user is logged in
$user = empty($_SESSION['token']) ? null : getLocallyFromToken();
if ($user === null) validateToken('poll');

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
if (empty($user)){
	$name = param('name');
	if (is_numeric($name)){
		error('"?" is not a valid name!',$name);
		$name = '';
	}
} else $name = $user->id;

$previous_selections = empty($name) ? null :$poll->get_selections($name);

if (!empty($options)) {
	if (empty($name)){
		error('You must provide a name!');
	} else {
		if (!empty($previous_selections)) $confirmed = false;
		if (param('confirm')=='on') $confirmed = true;
		if ($confirmed) {
			$poll->save_selection($_POST);
			info('Your selection has been saved.');
		} else {
			error('A user with name "◊" has already submitted selections. Enter another name or confirm to overwrite existing selections!',empty($user)?$name:$user->login);
			info('This will only alter options where you set a weight!');
		}
	}
}

include '../common_templates/head.php';

if (!empty($user)){
	include '../common_templates/main_menu.php';
	include 'menu.php';
} ?>

<fieldset>
	<legend><?= t('Poll "◊"',$poll->name) ?></legend>
	<?= markdown($poll->description)?>
	<form method="POST">
		<fieldset>
			<legend><?= t('Who are you?')?></legend>
			<?php include '../common_templates/messages.php'; ?>
			<?php if (empty($user)) { ?>
				<input type="text" name="name" value="<?= $name ?>"/>
			<?php } else { ?>
				<?= $user->login ?>
				<input type="hidden" name="name" value="<?= $user->id ?>" />
			<?php }?>
		</fieldset>
		<fieldset>
			<legend><?= t('Make your choices!')?></legend>
			<?= t('If you need more information, move your pointer over the weights or options!')?>
			<table>
				<tr>
					<th></th>
					<th colspan="<?= count($poll->weights()) ?>"><?= t('Weights')?></th>
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
				<?php foreach ($poll->options() as $oid => $option) {
					$classes = ['hover'];
					switch ($option->status){
						case Option::DISABLED:
							$classes[] = 'disabled'; break;
						case Option::HIDDEN:
							$classes[] = 'hidden'; break;
					}
				?>
				<tr class="<?= implode(' ', $classes)?>">
					<td>
						<?= $option->name ?>
						<span class="hidden">
							<?= markdown($option->description)?>
						</span>
					</td>
					<?php foreach ($poll->weights() as $weight => $meta) {
						$selected = false;
						if (isset($options[$oid])){ // one of the weights for this option has been selected
							if ($options[$oid]==$weight) $selected = true;
						} else { // none of the weights for this options was selected
							if (isset($previous_selections[$oid]['weight']) && $previous_selections[$oid]['weight'] == $weight) $selected = true; // option has been selected before
						}
					?>
					<td>
						<input type="radio" name="option[<?= $oid ?>]" value="<?= $weight ?>" <?= $selected?'checked="checked" ':'' ?>/>
					</td>
					<?php }?>
				</tr>
				<?php } ?>
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
				<tr>
					<th></th>
					<th colspan="<?= count($poll->weights()) ?>"><?= t('Weights')?></th>
				</tr>

			</table>
		</fieldset>
		<?php if (!$confirmed){ ?>
		<fieldset>
			<legend><?= t('Confirmation required') ?></legend>
			<?= t('Are you sure you want to overwrite the choices of ◊?',empty($user)?$name:$user->login)?>
			<label>
				<input type="checkbox" name="confirm" />
				<?= t('Yes, I am sure.')?>
			</label>
		</fieldset>
		<?php }?>
		<button type="submit"><?= t('Submit')?></button>
	</form>
</fieldset>

<?php if (!empty($user)) {
	if (isset($services['notes'])) { ?>
	<fieldset>
		<legend><?= t('Notes')?></legend>
		<?= request('notes','html',['uri'=>'poll:'.$poll->id,'context'=>t('Poll "◊"',$poll->name),'users'=>array_keys($poll->selections())],false,NO_CONVERSION); ?>
	</fieldset>
	<?php }} ?>
<?php include '../common_templates/closure.php';