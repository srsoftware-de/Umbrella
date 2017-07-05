<?php $title = 'Umbrella Invoice Management';

include '../bootstrap.php';
include 'controller.php';

require_login();

if ($sender = post('sender')){
	$id = create_invoice($sender,post('tax_number'),post('customer'));
	redirect($id.'/edit');
}

$contacts = request('contact','json_list');
$vcard = request('contact','json_assigned');


function conclude_vcard($vcard){
	$short = '';
	if (isset($vcard['N'])){
		$names = explode(';',$vcard['N']);
		$short = $names[2].' '.$names[1];
	}
	if (isset($vcard['ORG'])){		
		$org = str_replace(';', ', ', $vcard['ORG']);
		if ($short != '') $short.=', ';
		$short .= $org;		
	}
	return $short;
}

$tax_number = post('tax_number',$vcard['X-TAX-NUMBER']);

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
		</fieldset>
		
		<button type="submit">Save</button>		
	</fieldset>
</form>

<?php include '../common_templates/closure.php'; ?>
