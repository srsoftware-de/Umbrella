<?php

include '../bootstrap.php';
include 'controller.php';

$title = t('Umbrella: Contacts');
require_login('contact');

$id = param('id');
$vcard = VCard::load(['ids'=>$id]);

if (post('N')){
	$vcard->patch($_POST);
	$vcard->save();
	redirect('../index');
}
if (!isset($vcard->ADR)) $vcard->patch('ADR:post_box;extended;street;locality;region;postal code;country');
if (!isset($vcard->{'X-BANK-ACCOUNT'})) $vcard->{'X-BANK-ACCOUNT'} = '';
if (!isset($vcard->{'X-CUSTOMER-NUMBER'})) $vcard->{'X-CUSTOMER-NUMBER'} = '';
if (!isset($vcard->{'X-TAX-NUMBER'})) $vcard->{'X-TAX-NUMBER'} = '';
if (!isset($vcard->ORG)) $vcard->ORG = '';
if (!isset($vcard->{'X-COURT'})) $vcard->{'X-COURT'} = '';


function createAddressField($adr,$param = null,$index = null){
	$name = 'ADR';
	if ($param !== null){
		$name.='['.$param.']';
	}

	if ($index !== null){
		$name.='['.$index.']';
	}

	return '<fieldset>
	<legend>'.t('Address').($param?' ('.$param.')':'').'</legend>
	<label class="street">'.t('Street').'
					<input type="text" name="'.$name.'[street]" value="'.$adr->street.'" />
				</label>
				<label>'.t('Post Code').'
					<input type="text" name="'.$name.'[post_code]" value="'.$adr->post_code.'" />
				</label>
				<label class="location">'.t('City').'
					<input type="text" name="'.$name.'[locality]" value="'.$adr->locality.'" />
				</label>
				<label class="region">'.t('Region').'
					<input type="text" name="'.$name.'[region]" value="'.$adr->region.'" />
				</label>
				<label>'.t('Country').'
					<input type="text" name="'.$name.'[country]" value="'.$adr->country.'" />
				</label>
			</fieldset>';
}

function createNameField($name){
	return '<fieldset>
				<legend>'.t('Name').'</legend>
				<label>'.t('Prefix').'
					<input type="text" name="N[prefix]" value="'.$name->prefixes.'"/>
				</label>
				<label>'.t('First Name').'
					<input type="text" name="N[given]" value="'.$name->given.'"/>
				</label>
				<label>'.t('Additional names').'
					<input type="text" name="N[additional]" value="'.$name->additional.'"/>
				</label>
				<label>'.t('Last Name').'
					<input type="text" name="N[family]" value="'.$name->family.'"/>
				</label>
				<label>'.t('Suffix').'
					<input type="text" name="N[suffix]" value="'.$name->suffixes.'"/>
				</label>
			</fieldset>';
}

function createOtherField($key,$value,$param,$index = null,$multiline=false){
	$result= '<fieldset>
				<legend>'.t($key).($param?' ('.$param.')':'').'</legend>';

	if ($multiline){
		$result.='<textarea name="'.$key;
		if ($param) $result.='['.$param.']';
		if ($index !== null) $result.='['.$index.']';
		$result.='" />'.$value.'</textarea>';
	} else {
		$result.='<input type="text" name="'.$key;
		if ($param) $result.='['.$param.']';
		if ($index !== null) $result.='['.$index.']';
		$result.='" value="'.$value.'"/>';
	}
	$result.='</fieldset>';
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
	if ($key == 'X-BANK-ACCOUNT') return createOtherField($key,$value,$param,$index,MULTILINE);
	if ($key == 'X-COURT') return createOtherField($key,$value,$param,$index);
	if ($key == 'X-CUSTOMER-NUMBER') return createOtherField($key,$value,$param,$index);
	if ($key == 'X-TAX-NUMBER') return createOtherField($key,$value,$param,$index);
	if (strpos($key,'X-')===0) return '';
	return createOtherField($key,$value,$param,$index);
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
		<?php foreach($vcard as $key => $value){
			echo createFieldset($key,$value);
		}?>
		<button type="submit"><?= t('Save') ?></button>
	</fieldset>
</form>

<?php include '../common_templates/closure.php'; ?>
