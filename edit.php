<?php include 'controller.php';

$user = User::require_login();

if ($user_id = param('id')){
	$allowed = ($user->id == 1 || $user->id == $user_id);
	if ($allowed) {
		$u = User::load(['ids'=>$user_id]);
		if (!empty($_POST['login'])) $u->patch($_POST)->save();
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
		if (in_array($field, ['id','theme','pass','new_pass','dirty'])) continue; ?>
	<fieldset>
		<legend><?= t($field) ?></legend>
		<input type="text" name="<?= $field ?>" value="<?= htmlspecialchars($value) ?>" />
	</fieldset>

	<?php }?>

	<fieldset>
		<legend><?= t('new password (leave empty to not change you password)')?></legend>
		<input type="password" name="new_pass" autocomplete="new-password" />
	</fieldset>
	<fieldset>
		<legend><?= t('theme'); ?></legend>
		<select name="theme">
		<?php foreach ($themes as $thm) { ?>
			<option value="<?= $thm ?>" <?= $u->theme == $thm?'selected="true"':''?>><?= $thm ?></option>
		<?php } ?>
		</select>
	</fieldset>
	<button type=submit"><?= t('Save') ?></button>
</form>
<?php }
 include '../common_templates/closure.php'; ?>
