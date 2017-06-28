<?php $title = 'Umbrella Contact Management';

include '../bootstrap.php';
include 'controller.php';

require_login();
if (post('EMAIL')){
	$vcard = create_vcard($_POST);
	$name = $vcard['N'];	
	sort($name);
	$name[]=date('Y-m-d H.i.s');
	$filename = 'contacts/'.implode(' ',$name).'.VCF';
	$query = http_build_query(array('filename'=>$filename,'content'=>serialize_vcard($vcard)));	
	$response = request('files','store_text?'.$query);
	if ($response == 1){
		redirect('index');
	}
	error($response);
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
				<input type="text" name="N[2]" <?= ($name = post('N'))?'value="'.$name['2'].'"':''?>/>
			</label>
			<label>Last Name
				<input type="text" name="N[1]" <?= ($name = post('N'))?'value="'.$name['1'].'"':''?> />
			</label>						
		</fieldset>
		<fieldset>
			<legend>(primary) Email</legend>
			<input type="text" name="EMAIL"<?= ($email = post('EMAIL'))?'value="'.$email.'"':''?>  />									
		</fieldset>
		<fieldset>
			<legend>(primary) Address</legend>
			<label class="street">Street
				<input type="text" name="ADR[3]" <?= ($adr = post('ADR'))?'value="'.$adr['3'].'"':''?> />
			</label>
			<label>Post Code
				<input type="text" name="ADR[6]" <?= ($adr = post('ADR'))?'value="'.$adr['6'].'"':''?>/>
			</label>
			<label class="location">Location
				<input type="text" name="ADR[4]" <?= ($adr = post('ADR'))?'value="'.$adr['4'].'"':''?>/>
			</label>			
			<label class="region">Region
				<input type="text" name="ADR[5]" <?= ($adr = post('ADR'))?'value="'.$adr['5'].'"':''?>/>
			</label>
			<label>Country
				<input type="text" name="ADR[7]" <?= ($adr = post('ADR'))?'value="'.$adr['7'].'"':''?>/>
			</label>			
						
		</fieldset>
		<button type="submit">Save</button>
	</fieldset>
</form>

<?php include '../common_templates/closure.php'; ?>
