<?php include 'controller.php';

$user = User::require_login();

if ($user_id = param('id')){
	$allowed = ($user->id == 1 || $user->id == $user_id);
	if ($allowed) {
		$u = User::load(['ids'=>$user_id]);
		if (!empty($_POST['login'])) {

			$md = Message::SEND_INSTANTLY;
			$delivery = param('delivery');
			if (empty($delivery)){
				$md = Message::SEND_NOT;
			} elseif (isset($delivery[0])){
				$md = Message::SEND_INSTANTLY;
			} else {
				foreach (array_keys(param('delivery')) as $flag) $md += $flag;
			}
			$_POST['message_delivery'] = $md;

			if (!empty($_POST['new_pass']) && $_POST['new_pass'] != $_POST['new_pass_repeat']){
				error('Passwords do not match!');
			} else $u->patch($_POST)->save();

			if ($u->id == $user->id) $user = $u; // if we updated the updating user, refresh this
		}
	} else {
		error('Currently, only admin can edit other users!');
		redirect('../index');
	}
} else redirect(getUrl('user',$user->id.'/edit'));
$themes = get_themes();


include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include '../common_templates/messages.php';

if ($allowed){ ?>
<form method="POST">
	<?php foreach ($u as $field => $value) {
		if (in_array($field, ['dirty','id','last_logoff','message_delivery','new_pass','new_pass_repeat','pass','theme'])) continue; ?>
	<fieldset>
		<legend><?= t($field) ?></legend>
		<input type="text" name="<?= $field ?>" value="<?= htmlspecialchars($value) ?>" />
	</fieldset>

	<?php } ?>

	<fieldset>
		<legend><?= t('Notificatin settings')?></legend>
		<label>
			<input type="checkbox" name="delivery[<?= Message::SEND_INSTANTLY ?>]" <?= $user->message_delivery == Message::SEND_INSTANTLY ? 'checked="checked"':''?> />
			<?= t("Send instantly")?>
		</label><br/>		<label>
			<input type="checkbox" name="delivery[<?= Message::SEND_AT_8 ?>]" <?= $user->message_delivery & Message::SEND_AT_8 ? 'checked="checked"':''?> />
			<?= t('Send at 8 AM')?>
		</label><br/>
		<label>
			<input type="checkbox" name="delivery[<?= Message::SEND_AT_10 ?>]"<?= $user->message_delivery & Message::SEND_AT_10 ? 'checked="checked"':''?>  />
			<?= t('Send at 10 AM')?>
		</label><br/>
		<label>
			<input type="checkbox" name="delivery[<?= Message::SEND_AT_12 ?>]" <?= $user->message_delivery & Message::SEND_AT_12 ? 'checked="checked"':''?> />
			<?= t('Send at 12 PM')?>
		</label><br/>
		<label>
			<input type="checkbox" name="delivery[<?= Message::SEND_AT_14 ?>]" <?= $user->message_delivery & Message::SEND_AT_14 ? 'checked="checked"':''?> />
			<?= t('Send at 2 PM')?>
		</label><br/>
		<label>
			<input type="checkbox" name="delivery[<?= Message::SEND_AT_16 ?>]" <?= $user->message_delivery & Message::SEND_AT_16 ? 'checked="checked"':''?> />
			<?= t('Send at 4 PM')?>
		</label><br/>
		<label>
			<input type="checkbox" name="delivery[<?= Message::SEND_AT_18 ?>]" <?= $user->message_delivery & Message::SEND_AT_18 ? 'checked="checked"':''?> />
			<?= t('Send at 6 PM')?>
		</label><br/>
		<label>
			<input type="checkbox" name="delivery[<?= Message::SEND_AT_20 ?>]" <?= $user->message_delivery & Message::SEND_AT_20 ? 'checked="checked"':''?> />
			<?= t('Send at 8 PM')?>
		</label><br/>
		<?= t('If no time is selected, messages will not be sent via mail.')?>
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
