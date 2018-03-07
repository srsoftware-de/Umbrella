<?php

include '../bootstrap.php';
include 'controller.php';

$title = t('Umbrella: Contacts');
require_login('contact');

$id = param('id');
$vcard = VCard::load(['ids'=>$id]);
assert($vcard !== null,'Was not able to lod this vcard from the database');

error('this form is not functional, yet!');

include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>


<form method="POST">
	<fieldset>
		<legend><?= t('Add phone number to ?',$vcard->name(BEAUTY)) ?></legend>
		<input type="text" name="number" />
		<label>
			<input type="checkbox" name="type[]" value="cell" />
			<?= t('mobile phone')?>
		</label>
		<label>
			<input type="checkbox" name="type[]" value="home" />
			<?= t('home phone')?>
		</label>
		<label>
			<input type="checkbox" name="type[]" value="work" />
			<?= t('work phone')?>
		</label>
		<label>
			<input type="checkbox" name="type[]" value="fax" />
			<?= t('fax')?>
		</label>
		<button type="submit"><?= t('Save') ?></button>
	</fieldset>
</form>

<fieldset>
	<legend>RAW</legend>
	<pre><?= debug($vcard)?></pre>
</fieldset>
<?php include '../common_templates/closure.php'; ?>
