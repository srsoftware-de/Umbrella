<?php 

if (!isset($services['files'])) die('Contact service requres file service to be active!');
	
function create_vcard($data){
	$vcard = array();
	$vcard['BEGIN'] = 'VCARD';
	$vcard['VERSION'] = '4.0';
	
	foreach ($data as $key => $value) {
		$value = str_replace(array("\r\n","\r","\n"), ';', $value);
		$vcard[$key] = $value;
	}
	
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

function unserialize_vcard($raw){
	$lines = explode("\r\n", $raw);
	$vcard = array();	
	foreach ($lines as $line){		
		$map = explode(':', $line,2);
		$key = $map[0];
		$val = $map[1];
		$vcard[$key]=$val; 
	}
	return $vcard;
}

function read_contacts(){
	$files_in_contact_folder = request('files','list?folder=contacts');
	$cards = array();	
	foreach ($files_in_contact_folder as $hash => $file_info){
		$filename = $file_info['path'];
		$extension = strtoupper(substr($filename, -4));
		if ($extension != '.VCF') continue;
		$file_content = request('files','download?file='.$hash,false,false);
		$cards[$hash]=array('filename'=>basename($filename),'vcard'=>unserialize_vcard($file_content));
	}
	return $cards;	
}
?>