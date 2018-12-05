<?php include 'controller.php';

require_login('document');

$doc_types = DocumentType::load();
$companies = request('company','json');

$company = null;
if (($company_id = param('company')) && isset($companies[$company_id])) $company = $companies[$company_id];
if ($company === null) redirect('.');

$contacts = request('contact','json',null,false,OBJECT_CONVERSION);
if (empty($contacts)) warn('You can not select a customer because your contact list is empty. Create a contact in the contacts module first.');
if ($customer_contact_id = post('customer')){
	$customer_vcard  = $contacts->{$customer_contact_id};

	if (empty($customer_vcard->{'X-CUSTOMER-NUMBER'})) set_customer_number($customer_vcard,$company);

	$_POST['customer'] = address_from_vcard($customer_vcard);
	$_POST['customer_number'] = isset($customer_vcard->{'X-CUSTOMER-NUMBER'}) ? $customer_vcard->{'X-CUSTOMER-NUMBER'} : null;
	$_POST['customer_tax_number'] = isset($customer_vcard->{'X-TAX-NUMBER'}) ? $customer_vcard->{'X-TAX-NUMBER'} : null;
	if (isset($customer_vcard->EMAIL)){
		$email = $customer_vcard->EMAIL;
		while (is_array($email)) $email = reset($email);
		$_POST['customer_email'] = $email->val;
	}
	$document = new Document($company);
	$document->patch($_POST);

	$company_settings = CompanySettings::load($company,$document->type_id);
	$company_settings->applyTo($document);


	$document->template_id = 0; // TODO impelement by selection
	$document->save();
	$company_settings->save();
	redirect($document->id.'/view');
}

function customer_num($contact){
	if (isset($contact->{'X-CUSTOMER-NUMBER'}) && !empty($contact->{'X-CUSTOMER-NUMBER'})) return ' ('.$contact->{'X-CUSTOMER-NUMBER'}.')';
	return '';
}

$contacts_sorted = [];
foreach ($contacts as $contact){
	$short = conclude_vcard($contact);
	$contacts_sorted[$short] = $contact;
}
ksort($contacts_sorted,SORT_FLAG_CASE|SORT_STRING);
include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<form method="POST" class="document">
	<fieldset>
		<legend><?= t('Create new document') ?></legend>
		<fieldset class="customer">
			<legend><?= t('Customer') ?></legend>
			<select name="customer">
				<option value=""><?= t('== select a customer ==') ?></option>
				<?php foreach ($contacts_sorted as $short => $contact) { ?>
				<option value="<?= $contact->id ?>" <?= (post('customer')==$contact->id)?'selected="true"':''?>><?= $short.customer_num($contact) ?></option>
				<?php }?>
			</select>
		</fieldset>
		<fieldset class="document_type">
			<legend><?= t('Document type') ?></legend>
			<select name="type_id">
			<?php foreach ($doc_types as $type_id => $doc_type){ ?>
				<option value="<?= $type_id ?>"><?= t($doc_type->name)?></option>
			<?php } ?>
			</select>
		</fieldset>
		<fieldset class="sender">
			<legend>Sender</legend>
			<textarea name="sender"><?= $company['address'] ?></textarea>
			<fieldset>
				<legend><?= t('Tax number') ?></legend>
				<input name="tax_number" value="<?= $company['tax_number'] ?>" />
			</fieldset>
			<fieldset>
				<legend><?= t('Bank account')?></legend>
				<textarea name="bank_account"><?= $company['bank_account'] ?></textarea>
			</fieldset>
			<fieldset>
				<legend><?= t('Local court')?></legend>
				<input type="text" name="court" value="<?= $company['court'] ?>"/>
			</fieldset>
		</fieldset>

		<button type="submit"><?= t('Save') ?></button>
	</fieldset>
</form>

<?php include '../common_templates/closure.php'; ?>
