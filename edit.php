<?php include 'controller.php';

$user = User::require_login();

if ($user_id = param('id')){
	$allowed = ($user->id == 1 || $user->id == $user_id);
	if ($allowed) {
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
		<select name="message_delivery">
			<?php foreach ([
					Message::DELIVER_INSTANTLY,
					Message::COLLECT_TILL__8,
					Message::COLLECT_TILL_10,
					Message::COLLECT_TILL_12,
					Message::COLLECT_TILL_14,
					Message::COLLECT_TILL_16,
					Message::COLLECT_TILL_18,
					Message::COLLECT_TILL_20
			] as $option) { ?>
			<option value="<?= $option ?>" <?= $u->message_delivery == $option ? 'selected="selected"':'' ?>><?= t($option)?></option>
			<?php } ?>
		</select>
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
