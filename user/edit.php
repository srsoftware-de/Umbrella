<?php $title = 'Umbrella Users';

include '../bootstrap.php';
include 'controller.php';

require_user_login();
$user_id = param('id');

$allowed = ($user->id == 1 || $user->id == $user_id);
if ($allowed) {
	$u = load_user($user_id);
	if (!empty($_POST)){
		foreach ($_POST as $key => $value){
			if ($key == 'new_pass') continue;		
			$u->{$key} = $value;
		}
		update_user($u);
		if ($new_pass = post('new_pass')){
			alter_password($u,$new_pass);
			$u = load_user($user_id);
		}
	}
} else {
	error('Currently, only admin can edit other users!');
	redirect('../index');
}
$themes = get_themes();

include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php';

if ($allowed){
?>
<form method="POST">
	<?php foreach ($u as $field => $value) {
		if (in_array($field, ['id','theme','pass'])) continue; ?>
	<fieldset>
		<legend><?= t($field) ?></legend>
		<input type="test" name="<?= $field ?>" value="<?= $value ?>" />
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
			<option value="<?= $thm ?>" <?= $theme == $thm?'selected="true"':''?>><?= $thm ?></option>
		<?php } ?>
		</select>
	</fieldset>
	<button type=submit"><?= t('Save') ?></button>
</form>
<?php }
 include '../common_templates/closure.php'; ?>
