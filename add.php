<?php $title = 'Umbrella Invoice Management';

include '../bootstrap.php';
include 'controller.php';

require_login('invoice');

$companies = request('company','json');

$company = null;
if (($company_id = param('company')) && isset($companies[$company_id])){
	$company = $companies[$company_id];	
}
if ($company === null) redirect('.');

$company_settings = CompanySettings::load($company);

$contacts = request('contact','json_list');

if ($customer_contact_id = post('customer')){
	$customer_vcard  = $contacts[$customer_contact_id];
	$_POST['customer'] = address_from_vcard($customer_vcard);
	$_POST['customer_number'] = isset($customer_vcard['X-CUSTOMER-NUMBER']) ? $customer_vcard['X-CUSTOMER-NUMBER'] : null;
	if (isset($customer_vcard['EMAIL'])){
		$email = $customer_vcard['EMAIL'];
		while (is_array($email)){
			if (isset($email['TYPE=work'])) {
				$email = $email['TYPE=work'];
			} else {
				$email = reset($email);
			}
		}
		$_POST['customer_email'] = $email;
	}	
	$invoice = new Invoice($company);	
	$invoice->patch($_POST);
	$company_settings->applyTo($invoice);
	$invoice->template_id = 0; // TODO impelement by selection
	$invoice->save();
	$company_settings->save();
	redirect($invoice->id.'/view');
} 

include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<form method="POST" class="invoice">
	<fieldset>
		<legend><?= t('Create new invoice') ?></legend>
		<fieldset class="customer">		
			<legend><?= t('Customer') ?></legend>
			<select name="customer">
				<option value="">== select a customer ==</option>
				<?php foreach ($contacts as $contact_id => $contact) { ?>
				<option value="<?= $contact_id ?>" <?= (post('customer')==$contact_id)?'selected="true"':''?>><?= conclude_vcard($contact)?></option>
				<?php }?>				
			</select>			
		</fieldset>
		<fieldset class="document_type">		
			<legend><?= t('Document type') ?></legend>
			<select name="type">
				<option value="<?= Invoice::TYPE_INVOICE?>"><?= t('invoice')?></option>								
				<option value="<?= Invoice::TYPE_OFFER?>"><?= t('offer')?></option>								
				<option value="<?= Invoice::TYPE_CONFIRMATION?>"><?= t('confirmation')?></option>								
				<option value="<?= Invoice::TYPE_REMINDER?>"><?= t('reminder')?></option>
			</select>			
		</fieldset>
		<fieldset class="sender">
			<legend>Sender</legend>
			<textarea name="sender"><?= $company['address'] ?></textarea>			
			<fieldset>
				<legend>Tax number</legend>
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
