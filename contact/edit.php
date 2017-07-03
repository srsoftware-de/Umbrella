<?php $title = 'Umbrella Contacts';

include '../bootstrap.php';
include 'controller.php';

require_login();

$id = param('id');
$contact = read_contacts($id);
assert($contact !== null,'Was not able to lod this vcard from the database');
$vcard = $contact[$id];
if (post('EMAIL')){
	$vcard = create_vcard($_POST);
	store_vcard($vcard,(int)$id);
	redirect('../index');
}

include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<form method="POST">
	<fieldset>
		<legend>Create new Contact</legend>
		<fieldset>
			<legend>Name</legend>
			<label>First Name
				<input type="text" name="N[2]" <?= ($name = explode(';',$vcard['N']))?'value="'.$name['2'].'"':''?>/>
			</label>
			<label>Last Name
				<input type="text" name="N[1]" <?= ($name = explode(';',$vcard['N']))?'value="'.$name['1'].'"':''?> />
			</label>						
		</fieldset>
		<fieldset>
			<legend>(primary) Email</legend>
			<input type="text" name="EMAIL"<?= ($email = $vcard['EMAIL'])?'value="'.$email.'"':''?>  />									
		</fieldset>
		<fieldset>
			<legend>Organization</legend>
			<textarea name="ORG"><?= ($org = $vcard['ORG'])?$org:'' ?></textarea>
		</fieldset>
		<fieldset>
			<legend>(primary) Address</legend>
			<label class="street">Street
				<input type="text" name="ADR[3]" <?= ($adr = explode(';',$vcard['ADR']))?'value="'.$adr['3'].'"':''?> />
			</label>
			<label>Post Code
				<input type="text" name="ADR[6]" <?= ($adr = explode(';',$vcard['ADR']))?'value="'.$adr['6'].'"':''?>/>
			</label>
			<label class="location">Location
				<input type="text" name="ADR[4]" <?= ($adr = explode(';',$vcard['ADR']))?'value="'.$adr['4'].'"':''?>/>
			</label>			
			<label class="region">Region
				<input type="text" name="ADR[5]" <?= ($adr = explode(';',$vcard['ADR']))?'value="'.$adr['5'].'"':''?>/>
			</label>
			<label>Country
				<input type="text" name="ADR[7]" <?= ($adr = explode(';',$vcard['ADR']))?'value="'.$adr['7'].'"':''?>/>
			</label>			
						
		</fieldset>
				<fieldset>
			<legend>Customer Relationship</legend>
			<label class="customer_number">Customer Number
				<input type="text" name="X-CUSTOMER-NUMBER" <?= ($num = $vcard['X-CUSTOMER-NUMBER'])?'value="'.$num.'"':''?>/>
			</label>
			<label>Tax number
				<input type="text" name="X-TAX-NUMBER" <?= ($num = $vcard['X-TAX-NUMBER'])?'value="'.$num.'"':''?>/>
			</label>			
						
		</fieldset>
		<button type="submit">Save</button>
	</fieldset>
</form>

<?php include '../common_templates/closure.php'; ?>
