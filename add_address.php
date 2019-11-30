<?php include 'controller.php';

require_login('contact');

$id = param('id');
$vcard = VCard::load(['ids'=>$id]);
assert($vcard !== null,'Was not able to lod this vcard from the database');

if (param('ADR#0')) {
	$vcard->patch($_POST,true);
	$vcard->save();
	redirect(getUrl('contact'));
}

$addr = new Address([]);


include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>


<form method="POST">
	<?= $addr->editFields() ?>
	<button type="submit"><?= t('Save') ?></button>
</form>
<?php include '../common_templates/closure.php'; ?>
