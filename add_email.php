<?php include 'controller.php';

require_login('contact');

$id = param('id');
$vcard = VCard::load(['ids'=>$id]);
if ($vcard == null) throw new Exception('Was not able to lod this vcard from the database');

if (param('EMAIL')) {
	$vcard->patch($_POST,true);
	$vcard->save();
	redirect(getUrl('contact'));
}

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>


<form method="POST">
	<fieldset>
		<legend><?= t('Add email address to ?',$vcard->name(BEAUTY)) ?></legend>
		<input type="text" name="EMAIL[val]" />
		<label>
			<input type="checkbox" name="EMAIL[param][TYPE][]" value="home" />
			<?= t('home')?>
		</label>
		<label>
			<input type="checkbox" name="EMAIL[param][TYPE][]" value="work" />
			<?= t('work')?>
		</label>
		<button type="submit"><?= t('Save') ?></button>
	</fieldset>
</form>
<?php include '../common_templates/closure.php'; ?>
