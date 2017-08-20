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
	redirect('../index');
}

if (!isset($vcard['ADR'])) $vcard['ADR'] = ['street','locality','region','pcode','country'];
if (!isset($vcard['X-TAX-NUMBER'])) $vcard['X-TAX-NUMBER'] = '';

function createAddressField($value,$param = null,$index = null){
	$name = 'ADR';
	$adr = explode(';', $value);
	if ($param !== null){
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

function createOtherField($key,$value,$param,$index = null){
	$result= '<fieldset>
				<legend>'.t($key).($param?' ('.$param.')':'').'</legend>
				<input type="text" name="'.$key;
	if ($param) $result.='['.$param.']';
	if ($index !== null) $result.='['.$index.']';
	$result.='" value="'.$value.'"/>				
			</fieldset>';
	return $result;
}

function createField($key,$value,$param = null,$index = null){
	if (is_array($value)){		
		$result = '';
		foreach ($value as $k => $v){
			if (strpos($k, '=') === false){
				$index = $k;
				$result .= createField($key, $v, $param, $index);
			} else {
				$result .= createField($key, $v, $k, null);
			}
		}		
		return $result;
	}
	if ($key == 'N') return createNameField($value);
	if ($key == 'ADR') return createAddressField($value,$param,$index);
	if ($key == 'X-TAX-NUMBER') return createOtherField($key,$value,$param,$index);
	if (strpos($key,'X-')===0) return '';
	return createOtherField($key,$value,$param,$index);
}

function createFieldset($key,$value){
	if (in_array($key, ['BEGIN','END','VERSION', 'PRODID','UID','REV'])) return;	
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
