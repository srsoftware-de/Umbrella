<?php $title = 'Umbrella Contacts';

include '../bootstrap.php';
include 'controller.php';

require_login();

$id = param('id');
$contact = read_contacts($id);
assert($contact !== null,'Was not able to lod this vcard from the database');
$vcard = $contact[$id];
if (post('N')){
	$vcard = update_vcard($vcard);
	store_vcard($vcard,(int)$id);
	//redirect('../index');
}

function createAddressField($value,$param = null,$index = null){
	$name = 'ADR';
	$adr = explode(';', $value);
	if ($param !== null){
		$param = implode(';', $param);
		$name.='['.$param.']';		
	}
	
	if ($index !== null){
		$name.='['.$index.']';
	}
	
	return '<fieldset>
	<legend>'.t('Address').($param?' ('.$param.')':'').'</legend>
	<label class="street">'.t('Street').'
					<input type="text" name="'.$name.'[street]" value="'.$adr['2'].'" />
				</label>
				<label>'.t('Post Code').'
					<input type="text" name="'.$name.'[pcode]" value="'.$adr['5'].'" />
				</label>
				<label class="location">'.t('City').'
					<input type="text" name="'.$name.'[locality]" value="'.$adr['3'].'" />
				</label>			
				<label class="region">'.t('Region').'
					<input type="text" name="'.$name.'[region]" value="'.$adr['4'].'" />
				</label>
				<label>'.t('Country').'
					<input type="text" name="'.$name.'[country]" value="'.$adr['6'].'" />
				</label>	
			</fieldset>';
}



function createNameField($name){
	$name = explode(';', $name);
	return '<fieldset>
				<legend>'.t('Name').'</legend>
				<label>'.t('Prefix').'
					<input type="text" name="N[prefix]" value="'.$name['3'].'"/>
				</label>
				<label>'.t('First Name').'
					<input type="text" name="N[given]" value="'.$name['1'].'"/>
				</label>
				<label>'.t('Additional names').'
					<input type="text" name="N[additional]" value="'.$name['2'].'"/>
				</label>
				<label>'.t('Last Name').'
					<input type="text" name="N[surname]" value="'.$name['0'].'"/>
				</label>
				<label>'.t('Suffix').'
					<input type="text" name="N[suffix]" value="'.$name['4'].'"/>
				</label>
			</fieldset>';
}

function createOtherField($key,$value,$key_param,$index = null){
	if ($key_param !== null) $key_param = implode(';', $key_param);
	$result= '<fieldset>
				<legend>'.t($key).($key_param?' ('.$key_param.')':'').'</legend>
				<input type="text" name="'.$key;
	if ($key_param) $result.='['.$key_param.']';
	if ($index !== null) $result.='['.$index.']';
	$result.='" value="'.$value.'"/>				
			</fieldset>';
	return $result;
}

function createField($key,$value,$key_param = null,$index = null){
	if (is_array($value)){
		$result = '';
		foreach ($value as $i => $val){
			$result .= createField($key, $val, $key_param, $i);
		}
		return $result;
	}
	if ($key == 'N') return createNameField($value);
	if ($key == 'ADR') return createAddressField($value,$key_param,$index);
	if (strpos($key,'X-')===0) return '';
	return createOtherField($key,$value,$key_param,$index);
}

function createFieldset($key,$value){
	if (in_array($key, ['BEGIN','END','VERSION', 'PRODID','UID','REV'])) return;
	$key_parts = explode(';', $key);
	if (count($key_parts) == 1) return createField($key,$value);
	$key = array_shift($key_parts);
	return createField($key,$value,$key_parts);
}

include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<form method="POST">
	<fieldset>
		<legend><?= t('Edit Contact') ?></legend>
		<?php foreach($vcard as $key => $value){
			echo createFieldset($key,$value);
		}?>
		<button type="submit">Save</button>
	</fieldset>
</form>

<?php include '../common_templates/closure.php'; ?>
