<?php $title = 'Umbrella Company Management';

include '../bootstrap.php';
include 'controller.php';

require_login('company');

$id = param('id');
$company = reset(Company::load($id));
if ($data = param('company')){
	$company->patch($data);
	$company->save();
}


include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<form method="POST">
	<fieldset>
		<legend><?= t('Edit company') ?></legend>
		<?php foreach (Company::fields() as $field => $props) {
			if (!is_array($props)) $props = [$props];
			if ($field === 'id') { ?>
			<input type="hidden" name="company[<?= $field ?>]" value="<?= $company->id ?>"/>
			<?php continue; } ?>
		<fieldset>
			<legend><?= t($field)?></legend>



			<?php if (in_array('TEXT',$props)) { ?>
			<textarea name="company[<?= $field ?>]"><?= $company->{$field} ?></textarea>
			<?php } ?>

			<?php if (array_key_exists('VARCHAR',$props)) { ?>
			<input type="text" maxlength="<?= $props['VARCHAR'] ?>" name="company[<?= $field ?>]" value="<?= htmlspecialchars($company->{$field}) ?>" />
			<?php } ?>

			<?php if (in_array('INT',$props)) { ?>
			<input type="number" name="company[<?= $field ?>]" value="<?= htmlspecialchars($company->{$field}) ?>" />
			<?php } ?>

		</fieldset>
		<?php }?>
		<input type="submit" />
	</fieldset>

</form>

<?php include '../common_templates/closure.php'; ?>
