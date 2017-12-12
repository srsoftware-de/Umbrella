<?php $title = 'Umbrella Company Management';

include '../bootstrap.php';
include 'controller.php';

require_login('company');

if ($data = param('company')){
	$company = new Company($data['name']);
	$company->patch($data);
	$company->save();
	debug($company);
}

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<form method="POST">
	<fieldset>
		<legend><?= t('add new company') ?></legend>
		<?php foreach (Company::fields() as $field => $props) {
			if (!is_array($props)) $props = [$props];
			if (in_array($field,['id','logo'])) continue; // logo present for later use, disabled for now
		?>
		<fieldset>
			<legend><?= t($field)?></legend>
			<?php if (in_array('TEXT',$props)) { ?>
			<textarea name="company[<?= $field ?>]"></textarea>
			<?php } ?>
			
			<?php if (array_key_exists('VARCHAR',$props)) { ?>
			<input type="text" maxlength="<?= $props['VARCHAR'] ?>" name="company[<?= $field ?>]" value="<?= array_key_exists('DEFAULT',$props)?$props['DEFAULT']:'' ?>" />
			<?php } ?>
			
			<?php if (in_array('INT',$props)) { ?>
			<input type="number" name="company[<?= $field ?>]" value="<?= array_key_exists('DEFAULT',$props)?$props['DEFAULT']:'' ?>" />
			<?php } ?>
			
		</fieldset>
		<?php }?>
		<input type="submit" />
	</fieldset>

</form>

<?php include '../common_templates/closure.php'; ?>
