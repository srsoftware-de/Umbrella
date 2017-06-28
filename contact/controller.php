<?php 

if (!isset($services['files'])) die('Contact service requres file service to be active!');
	
function list_contact_files($user_id = null){
		assert(is_numeric($user_id),'No valid user id passed to list contacts!');
		$files_in_contact_folder = request('files','list?folder=contacts');	
		//debug($files_in_contact_folder);	
		foreach ($files_in_contact_folder as $hash => $data){
			$extension = strtoupper(substr($data['path'], -4));
			if ($extension != '.VCF') unset($files_in_contact_folder[$hash]);
		}
		return $files_in_contact_folder;
}

function create_vcard($data){
	$vcard = array();
	$vcard['BEGIN'] = 'VCARD';
	$vcard['VERSION'] = '4.0';
	
	foreach ($data as $key => $value) $vcard[$key] = $value;
	
	$vcard['END'] = 'VCARD';
	return $vcard;
}

function serialize_vcard($vcard){
	$result = '';
	foreach ($vcard as $key => $value){
		if (is_array($value)){
			$index = 1;
			$val = '';
			while (!empty($value)){
				if (isset($value[$index])){
					$val.=$value[$index];
					unset($value[$index]);
				}
	
				if (!empty($value)){
					$val.=';';
					$index++;
				}
			}
			$value = $val;
		}
	
		$result .= $key.':'.$value."\r\n";
	}
	return $result;	
}
?>