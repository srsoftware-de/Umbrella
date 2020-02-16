<?php include 'controller.php';

$user = User::require_login();

if ($user_id = param('id')){
	$allowed = ($user->id == 1 || $user->id == $user_id);
	if ($allowed) {
		$md = 0;
		foreach (array_keys(param('delivery')) as $flag) $md += $flag;
		$_POST['message_delivery'] = $md;
		$u = User::load(['ids'=>$user_id]);
		if (!empty($_POST['login'])) {
			if (!empty($_POST['new_pass']) && $_POST['new_pass'] != $_POST['new_pass_repeat']){
				error('Passwords do not match!');
			} else $u->patch($_POST)->save();
		}
	} else {
		error('Currently, only admin can edit other users!');
		redirect('../index');
	}
} else error('No user ID passed to user/edit!');
$themes = get_themes();

include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php';

if ($allowed){ ?>
<form method="POST">
	<?php foreach ($u as $field => $value) {
		if (in_array($field, ['dirty','id','last_logoff','message_delivery','new_pass','new_pass_repeat','pass','theme'])) continue; ?>
	<fieldset>
		<legend><?= t($field) ?></legend>
		<input type="text" name="<?= $field ?>" value="<?= htmlspecialchars($value) ?>" />
	</fieldset>

	<?php }?>

	<fieldset>
		<legend><?= t('Notificatin settings')?></legend>
		<label>
			<input type="checkbox" name="delivery[<?= Message::SEND_AT_8 ?>]" />
			<?= t('SEND AT  8 AM')?>
		</label><br/>
		<label>
			<input type="checkbox" name="delivery[<?= Message::SEND_AT_10 ?>]" />
			<?= t('SEND AT 10 AM')?>
		</label><br/>
		<label>
			<input type="checkbox" name="delivery[<?= Message::SEND_AT_12 ?>]" />
			<?= t('SEND AT 12 PM')?>
		</label><br/>
		<label>
			<input type="checkbox" name="delivery[<?= Message::SEND_AT_14 ?>]" />
			<?= t('SEND AT  2 PM')?>
		</label><br/>
		<label>
			<input type="checkbox" name="delivery[<?= Message::SEND_AT_16 ?>]" />
			<?= t('SEND AT  4 PM')?>
		</label><br/>
		<label>
			<input type="checkbox" name="delivery[<?= Message::SEND_AT_18 ?>]" />
			<?= t('SEND AT  6 PM')?>
		</label><br/>
		<label>
			<input type="checkbox" name="delivery[<?= Message::SEND_AT_20 ?>]" />
			<?= t('SEND AT  8 PM')?>
		</label><br/>
		<?= t('If no time is selected, messages will be delivered instantly.')?>
	</fieldset>

	<fieldset>
		<legend><?= t('new password (leave empty to not change you password)')?></legend>
		<input type="password" name="new_pass" autocomplete="new-password" /><br/>
		<?= t('Repeat password:')?><br/>
		<input type="password" name="new_pass_repeat" autocomplete="new-password" />
	</fieldset>
	<fieldset>
		<legend><?= t('theme'); ?></legend>
		<select name="theme">
		<?php foreach ($themes as $thm) { ?>
			<option value="<?= $thm ?>" <?= $u->theme == $thm?'selected="true"':''?>><?= $thm ?></option>
		<?php } ?>
		</select>
	</fieldset>
	<button type="submit"><?= t('Save') ?></button>
</form>
<?php }
 include '../common_templates/closure.php'; ?>
