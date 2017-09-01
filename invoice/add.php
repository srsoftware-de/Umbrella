<?php $title = 'Umbrella Invoice Management';

include '../bootstrap.php';
include 'controller.php';

require_login();

$settings = get_settings($user);
if (!$settings) redirect('settings');

if ($sender = post('sender')){
	$customer_contact_id = post('customer');
	if ($customer_contact_id) {
		$id = create_invoice($sender,post('tax_number'),post('bank_account'),post('court'),post('customer'));
		redirect($id.'/edit');
	} else {
		error('No customer selected!');		
	}
}

$contacts = request('contact','json_list');
$vcard = request('contact','json_assigned');

$tax_number = post('tax_number',$vcard['X-TAX-NUMBER']);
$bank_account = str_replace(";", "\n", post('bank_account',$vcard['X-BANK-ACCOUNT']));
$local_court = post('court',$vcard['X-COURT']);

include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<form method="POST" class="invoice">
	<fieldset>
		<legend>Create new invoice</legend>
		<fieldset class="customer">
			<legend>Customer</legend>
			<select name="customer">
				<option value="">== select a customer ==</option>
				<?php foreach ($contacts as $contact_id => $contact) { ?>
				<option value="<?= $contact_id ?>" <?= (post('customer')==$contact_id)?'selected="true"':''?>><?= conclude_vcard($contact)?></option>
				<?php }?>				
			</select>			
		</fieldset>
		<fieldset class="sender">
			<legend>Sender</legend>
			<textarea name="sender"><?= vcard_address($vcard) ?></textarea>			
			<fieldset>
				<legend>Tax number</legend>
				<input name="tax_number" value="<?= $tax_number ?>" />
			</fieldset>
			<fieldset>
				<legend><?= t('Bank account')?></legend>
				<textarea name="bank_account"><?= $bank_account ?></textarea>
			</fieldset>		
			<fieldset>
				<legend><?= t('Local cout')?></legend>
				<input type="text" name="court" value="<?= $local_court ?>"/>
			</fieldset>
		</fieldset>
		
		<button type="submit">Save</button>		
	</fieldset>
</form>

<?php include '../common_templates/closure.php'; ?>
