<?php

include '../bootstrap.php';
include 'controller.php';

$title = t('Umbrella: Contacts');
require_login('contact');

$id = param('id');
$vcard = VCard::load(['ids'=>$id]);

if (post('N')){
	$vcard = new VCard(['id'=>(int)$id]);
	$vcard->patch($_POST,true);
	$vcard->save();
	redirect('../index');
}
if (!isset($vcard->ADR)) $vcard->patch('ADR:post_box;extended;street;locality;region;postal code;country');
if (!isset($vcard->{'X-BANK-ACCOUNT'})) $vcard->{'X-BANK-ACCOUNT'} = '';
if (!isset($vcard->{'X-CUSTOMER-NUMBER'})) $vcard->{'X-CUSTOMER-NUMBER'} = '';
if (!isset($vcard->{'X-TAX-NUMBER'})) $vcard->{'X-TAX-NUMBER'} = '';
if (!isset($vcard->ORG)) $vcard->ORG = '';
if (!isset($vcard->{'X-COURT'})) $vcard->{'X-COURT'} = '';

function createField($key,$value){
	if (is_array($value)){
		$fields = '';
		foreach ($value as $v) $fields .= createField($key, $v);
		return $fields;
	}
	if (is_object($value)) return $value->editFields();
	$result  = "<fieldset>\n";
	$result .= '<legend>'.t($key)."</legend>\n";
	$result .= '<input type="text" name="'.htmlspecialchars($key).'" value="'.htmlspecialchars($value).'"/>'."\n";
	$result .= "</fieldset>\n";
	return $result;
}

function createFieldset($key,$value){
	if (in_array($key, ['dirty','id','BEGIN','END','VERSION', 'PRODID','UID','REV'])) return;
	return createField($key,$value);
}

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<form method="POST">
	<fieldset>
		<legend><?= t('Edit Contact') ?></legend>
		<?php if (!isset($vcard->FN)) echo createFieldset('FN', '')?>
		<?php foreach($vcard as $key => $value) echo createFieldset($key,$value); ?>
		<button type="submit"><?= t('Save') ?></button>
	</fieldset>
</form>

<?php include '../common_templates/closure.php'; ?>
