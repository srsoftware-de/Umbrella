<?php include 'controller.php';

require_login('contact');

$id = param('id');
$vcard = VCard::load(['ids'=>$id]);
assert($vcard !== null,'Was not able to lod this vcard from the database');

if ($tel = param('TEL')) {
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
		<legend><?= t('Add phone number to ?',$vcard->name(BEAUTY)) ?></legend>
		<input type="text" name="TEL[val]" />
		<label>
			<input type="checkbox" name="TEL[param][TYPE][]" value="cell" />
			<?= t('mobile phone')?>
		</label>
		<label>
			<input type="checkbox" name="TEL[param][TYPE][]" value="home" />
			<?= t('home phone')?>
		</label>
		<label>
			<input type="checkbox" name="TEL[param][TYPE][]" value="work" />
			<?= t('work phone')?>
		</label>
		<label>
			<input type="checkbox" name="TEL[param][TYPE][]" value="fax" />
			<?= t('fax')?>
		</label>
		<button type="submit"><?= t('Save') ?></button>
	</fieldset>
</form>
<?php include '../common_templates/closure.php'; ?>
