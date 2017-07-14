<?php $title = 'Umbrella Invoice Management';

include '../bootstrap.php';
include 'controller.php';

require_login();

$id = param('id');
assert(is_numeric($id),'No valid invoice id passed to edit!');
$invoice = list_invoices($id);
assert(isset($invoice[$id]),'No invoice found or accessible for id = '.$id);
$invoice = $invoice[$id];

if ($customer = post('customer')){
	$keys = ['customer','customer_num','sender','tax_num','invoice_date','delivery_date','head','footer'];
	foreach ($keys as $key) {
		if ($value = post($key)) $invoice[$key] = $value;
	}
	save_invoice($id,$invoice);	
}


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
	debug($short);
}

$head_text = post('head','Wir erlauben uns, Ihnen die folgenden Positionen in Rechnung zu stellen:');
$foot_text = post('foot',"Zahlbar innerhalb von 14 Tagen ohne Abzug.\n\nUnberechtigt abgezogene Skontobeträge werden nachgefordert.\nLieferung frei Haus.\nGeben Sie bei Rückfragen und bei Überweisung bitte ihre Kundennummer und Rechnungsnummern an!\n\n Wir danken für Ihren Auftrag.");
$tax_number = post('tax_number','XXX');
$customer_number = post('customer_number','XXX');
$sender = post('sender','XXX');

include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<form method="POST" class="invoice">
	<fieldset>
		<legend>Edit invoice</legend>
		<fieldset class="customer">
			<legend>Customer</legend>
			<textarea name="customer"><?= $invoice['customer'] ?></textarea>
			<fieldset>
				<legend>Customer number</legend>
				<input name="customer_num" value="<?= $invoice['customer_num'] ?>" />
			</fieldset>		
			
		</fieldset>
		<fieldset class="sender">
			<legend>Sender</legend>
			<textarea name="sender"><?= $invoice['sender'] ?></textarea>			
			<fieldset>
				<legend>Tax number</legend>
				<input name="tax_number" value="<?= $invoice['tax_num'] ?>" />
			</fieldset>		
		</fieldset>
		
		<fieldset>
			<legend>Dates</legend>
			<label>Invoice Date
				<input name="invoice_date" value="<?= ($invoice_date = post('invoice_date'))?$invoice_date:date('Y-m-d')?>" />
			</label>
			<label>Delivery Date
				<input name="delivery_date" value="<?= ($delivery_date = post('delivery_date'))?$delivery_date:date('Y-m-d')?>" />
			</label>			
		</fieldset>
		<fieldset>
			<legend>
				Greeter/Head text
			</legend>
			<textarea name="head"><?= $head_text ?></textarea>
		</fieldset>
		<fieldset>
			<legend>Positions</legend>
			You will be able to add positions to the invoice in the next step.
		</fieldset>
		<fieldset>
			<legend>
				Footer text
			</legend>
			<textarea name="footer"><?= $foot_text ?></textarea>
		</fieldset>
		<button type="submit">Save</button>		
	</fieldset>
</form>

<?php include '../common_templates/closure.php'; ?>
