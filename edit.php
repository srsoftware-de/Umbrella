<?php include 'controller.php';

$user = User::require_login();

function eval_notification_options($options){
	$md = Message::SEND_INSTANTLY;
	if (empty($options)){
		$md = Message::SEND_NOT;
	} elseif (isset($options[0])){
		$md = Message::SEND_INSTANTLY;
	} else {
		foreach (array_keys($options) as $flag) $md += $flag;
	}
	return $md;
}

if ($user_id = param('id')){
	$allowed = ($user->id == 1 || $user->id == $user_id);
	if ($allowed) {
		$u = User::load(['ids'=>$user_id]);

		$projects = isset($services['project']) ? request('project','json') : null;

		if (!empty($_POST['login'])) {

			$settings = post('settings');
			if (isset($settings['notifications']) && is_array($settings['notifications'])){
				foreach ($settings['notifications'] as $realm => $notification_settings){
					switch ($realm){
						case 'default':
							$settings['notifications']['default'] = eval_notification_options($notification_settings);
							break;
						case 'project':
							foreach ($notification_settings as $pid => $p_sett) {
								$settings['notifications']['project'][$pid] = eval_notification_options($p_sett);
			}
							break;
					}
				}
				$_POST['settings'] = $settings;
			}

			$_POST['message_delivery'] = eval_notification_options(param('delivery'));

			if (!empty($_POST['new_pass']) && $_POST['new_pass'] != $_POST['new_pass_repeat']){
				error('Passwords do not match!');
			} else $u->patch($_POST)->save();

			if ($u->id == $user->id) $user = $u; // if we updated the updating user, refresh this
		}
	} else {
		error('Currently, only admin can edit other users!');
		redirect('../index');
	}
} else error('No user ID passed to user/edit!');
$themes = get_themes();

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include '../common_templates/messages.php';

if ($allowed){ ?>
<form method="POST">
	<?php foreach ($u as $field => $value) {
		if (in_array($field, ['dirty','id','last_logoff','message_delivery','new_pass','new_pass_repeat','pass','theme','settings'])) continue; ?>
	<fieldset>
		<legend><?= t($field) ?></legend>
		<input type="text" name="<?= $field ?>" value="<?= htmlspecialchars($value) ?>" />
	</fieldset>

	<?php } ?>

	<fieldset>
		<legend><?= t('Notificatin settings')?></legend>
		<table>
			<tr>
				<th><?= t('Context') ?></th>
				<th><?= t('Send instantly')?></th>
				<th><?= t('Send at 8 AM')?></th>
				<th><?= t('Send at 10 AM')?></th>
				<th><?= t('Send at 12 PM')?></th>
				<th><?= t('Send at 2 PM')?></th>
				<th><?= t('Send at 4 PM')?></th>
				<th><?= t('Send at 6 PM')?></th>
				<th><?= t('Send at 8 PM')?></th>
			</tr>
			<tr>
				<td><?= t('Common')?></td>
				<td><input type="checkbox" name="settings[notifications][default][<?= Message::SEND_INSTANTLY ?>]" <?= $user->settings['notifications']['default'] == Message::SEND_INSTANTLY ? 'checked="checked"':''?> /></td>
				<td><input type="checkbox" name="settings[notifications][default][<?= Message::SEND_AT_8 ?>]" <?= $user->settings['notifications']['default'] & Message::SEND_AT_8 ? 'checked="checked"':''?> /></td>
				<td><input type="checkbox" name="settings[notifications][default][<?= Message::SEND_AT_10 ?>]"<?= $user->settings['notifications']['default'] & Message::SEND_AT_10 ? 'checked="checked"':''?>  /></td>
				<td><input type="checkbox" name="settings[notifications][default][<?= Message::SEND_AT_12 ?>]" <?= $user->settings['notifications']['default'] & Message::SEND_AT_12 ? 'checked="checked"':''?> /></td>
				<td><input type="checkbox" name="settings[notifications][default][<?= Message::SEND_AT_14 ?>]" <?= $user->settings['notifications']['default'] & Message::SEND_AT_14 ? 'checked="checked"':''?> /></td>
				<td><input type="checkbox" name="settings[notifications][default][<?= Message::SEND_AT_16 ?>]" <?= $user->settings['notifications']['default'] & Message::SEND_AT_16 ? 'checked="checked"':''?> /></td>
				<td><input type="checkbox" name="settings[notifications][default][<?= Message::SEND_AT_18 ?>]" <?= $user->settings['notifications']['default'] & Message::SEND_AT_18 ? 'checked="checked"':''?> /></td>
				<td><input type="checkbox" name="settings[notifications][default][<?= Message::SEND_AT_20 ?>]" <?= $user->settings['notifications']['default'] & Message::SEND_AT_20 ? 'checked="checked"':''?> /></td>
			</tr>
			<?php if (!empty($projects)) { ?>
			<tr>
				<th colspan="10">
					<p><?= t('Projects')?>:</p>
					<?= t('Only open and pending projects are shown.')?>
					<?= t('Re-open project, if you want to update settings for closed or completed projects.')?>
				</th>
			</tr>
			<?php foreach ($projects as $project) {
				if ($project['status'] >= PROJECT_STATUS_COMPLETE) continue;
				$notification_settings = isset($user->settings['notifications']['project'][$project['id']]) ? isset($user->settings['notifications']['project'][$project['id']]) : 0;
				?>
			<tr>
				<td><?= $project['name'] ?></td>
				<td><input type="checkbox" name="settings[notifications][project][<?= $project['id']?>][<?= Message::SEND_INSTANTLY ?>]" <?= $notification_settings === Message::SEND_INSTANTLY ? 'checked="checked"':''?> /></td>
				<td><input type="checkbox" name="settings[notifications][project][<?= $project['id']?>][<?= Message::SEND_AT_8 ?>]"      <?= $notification_settings & Message::SEND_AT_8 ? 'checked="checked"':''?> /></td>
				<td><input type="checkbox" name="settings[notifications][project][<?= $project['id']?>][<?= Message::SEND_AT_10 ?>]"     <?= $notification_settings & Message::SEND_AT_10 ? 'checked="checked"':''?>  /></td>
				<td><input type="checkbox" name="settings[notifications][project][<?= $project['id']?>][<?= Message::SEND_AT_12 ?>]"     <?= $notification_settings & Message::SEND_AT_12 ? 'checked="checked"':''?> /></td>
				<td><input type="checkbox" name="settings[notifications][project][<?= $project['id']?>][<?= Message::SEND_AT_14 ?>]"     <?= $notification_settings & Message::SEND_AT_14 ? 'checked="checked"':''?> /></td>
				<td><input type="checkbox" name="settings[notifications][project][<?= $project['id']?>][<?= Message::SEND_AT_16 ?>]"     <?= $notification_settings & Message::SEND_AT_16 ? 'checked="checked"':''?> /></td>
				<td><input type="checkbox" name="settings[notifications][project][<?= $project['id']?>][<?= Message::SEND_AT_18 ?>]"     <?= $notification_settings & Message::SEND_AT_18 ? 'checked="checked"':''?> /></td>
				<td><input type="checkbox" name="settings[notifications][project][<?= $project['id']?>][<?= Message::SEND_AT_20 ?>]"     <?= $notification_settings & Message::SEND_AT_20 ? 'checked="checked"':''?> /></td>
			</tr>
			<?php } }?>
			<tr>
				<th><?= t('Context') ?></th>
				<th><?= t('Send instantly')?></th>
				<th><?= t('Send at 8 AM')?></th>
				<th><?= t('Send at 10 AM')?></th>
				<th><?= t('Send at 12 PM')?></th>
				<th><?= t('Send at 2 PM')?></th>
				<th><?= t('Send at 4 PM')?></th>
				<th><?= t('Send at 6 PM')?></th>
				<th><?= t('Send at 8 PM')?></th>
			</tr>
		</table>
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
