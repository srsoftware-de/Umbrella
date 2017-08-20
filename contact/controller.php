<?php

const MULTILINE=true;

if (!isset($services['files'])) die('Contact service requres file service to be active!');
	
function get_or_create_db(){
	if (!file_exists('db')){
		assert(mkdir('db'),'Failed to create contact/db directory!');
	}
	assert(is_writable('db'),'Directory contact/db not writable!');
	if (!file_exists('db/contacts.db')){
		$db = new PDO('sqlite:db/contacts.db');
		$db->query('CREATE TABLE contacts (id INTEGER PRIMARY KEY, DATA TEXT);');
		$db->query('CREATE TABLE contacts_users (contact_id INT NOT NULL, user_id INT NOT NULL, assigned BOOLEAN DEFAULT 0, PRIMARY KEY(contact_id, user_id));');
	} else {
		$db = new PDO('sqlite:db/contacts.db');
	}
	return $db;
}

function convert_val($key,$value){
	if ($key == 'N') return $value['surname'].';'.$value['given'].';'.$value['additional'].';'.$value['prefix'].';'.$value['suffix'];
	if ($key == 'ADR') return ';;'.$value['street'].';'.$value['locality'].';'.$value['region'].';'.$value['pcode'].';'.$value['country'];
	if (is_array($value)) debug('Multi-part entry could not be processed!',true);	
	return $value;
}

function update_vcard($vcard,$data=null){
	if ($data === null) $data = $_POST;
	//debug($vcard);
	//debug($data);
	foreach ($data as $key => $value) {
		if (is_array($value)){
			foreach ($value as $k => $v){
				if (is_numeric($k) || strpos($k, '=')){
					if (is_array($v)){
						foreach ($v as $a => $b){
							if (is_numeric($a) || strpos($a, '=')){
								if (is_array($b)) {
									debug('data['.$key.']['.$k.']['.$a.'] is array:');
									debug($b,1);
								}
								$vcard[$key][$k][$a] = $b;
							} else {
								$mp = convert_val($key, $v);
								//debug('Multipart: '.$mp);
								$vcard[$key][$k] = $mp;
								break;								
							}
						}
					} else {
						$vcard[$key][$k] = $v;
					}
				} else { // multipart!
					$mp = convert_val($key, $value);
					//debug('Multipart: '.$mp);
					$vcard[$key] = $mp;
					break;					
				}
			}
		} else {
			$vcard[$key] = $value;
		}
	}
	//debug($vcard,1);
	return $vcard;
}

function store_vcard($vcard = null, $id = null){
	global $user;
	assert(is_array($vcard),'No vcard data passsed to store_vcard');
	//debug($vcard);
	$data = serialize_vcard($vcard);
	//debug($data,1);
	$db = get_or_create_db();
	if (is_int($id)){
		$query  = $db->prepare('UPDATE contacts SET data=:data WHERE id = :id');
		assert($query->execute(array(':id'=>$id, ':data'=>$data)),'Was not able to update contact!');
	} else {
		$query = $db->prepare('INSERT INTO contacts (data) VALUES (:data)');
		assert($query->execute(array(':data'=>$data)),'Was not able to create new contact!');
		$id = $db->lastInsertId();
		$query =$db->prepare('INSERT INTO contacts_users (contact_id, user_id) VALUES (:cid, :uid)');
		assert($query->execute(array(':cid'=>$id,':uid'=>$user->id)),'Was not able to assign new contact to user');
	}
}

function break_lines($value){
	$value = str_replace(["\r\n","\r","\n"], '\n', $value);
	$result = '';
	$l=80;
	while (strlen($value)>$l){
		$result.=substr($value, 0,$l)."\n ";
		$value = substr($value,$l);
	}
	return $result.$value;  
}

function serialize_vcard($vcard){
	$result = '';
	foreach ($vcard as $key => $value){
		if (is_array($value)){
			foreach ($value as $k => $val){
				if (!is_numeric($k)) {
					if (is_array($val)){
						foreach ($val as $v) $result .= $key.';'.$k.':'.break_lines($v)."\r\n";
					} else $result .= $key.';'.$k.':'.break_lines($val)."\r\n";
				} else $result .= $key.':'.break_lines($val)."\r\n";
			}
			continue;
		}
		if (trim($value) == '') continue;
		$result .= $key.':'.break_lines($value)."\r\n";
	}
	return $result;	
}

function unserialize_vcard($raw){
	$raw = str_replace("\n ",'',$raw);
	$raw = str_replace("\r\n","\n",$raw);
	$lines = explode("\n", $raw);
	$vcard = array();	
	foreach ($lines as $line){
		if (empty($line)) continue;	
		
		$map = explode(':', $line,2);
		$key = $map[0];		
		$val = $map[1];
		
		if ($val == null || trim(str_replace(";", '', $val))=='') continue;
		$val = str_replace('\n', "\n", $val);
		
		$params = null;
		if (strpos($key, ';') !== false){ // key contains parameter
			$params = explode(';', $key);
			$key = array_shift($params);
			$params = implode(';', $params);
		}
		
		if ($params){			
			if (!isset($vcard[$key])) $vcard[$key]=array();
			if (!is_array($vcard[$key])) $vcard[$key] = array($vcard[$key]);
			if (isset($vcard[$key][$params])){
				$vcard[$key][$params] = array($vcard[$key][$params]);
				$vcard[$key][$params][] = $val;
			} else {			
				$vcard[$key][$params] = $val;
			}				
		} else {
			if (isset($vcard[$key])){
				if (is_array($vcard[$key])) {
					$vcard[$key][] = $val;
				} else {
					$vcard[$key] = array($vcard[$key],$val);
				}
			} else {
				$vcard[$key] = $val;
			}
		}
		
	}
	return $vcard;
}

function read_contacts($ids = null){
	global $user;
	if ($ids !== null && !is_array($ids)) $ids = array($ids);
	$db = get_or_create_db();
	$sql = 'SELECT * FROM contacts WHERE id IN (SELECT contact_id FROM contacts_users WHERE user_id = ?)';
	$args = array($user->id);
	if (is_array($ids)){		
		$qMarks = str_repeat('?,', count($ids) - 1) . '?';
		$sql .= ' AND id IN ('.$qMarks.')';
		$args = array_merge($args, $ids);
	}
	$query = $db->prepare($sql);
	assert($query->execute($args),'Was not able to query contacts for you!');	
	$results = $query->fetchAll(INDEX_FETCH);
	$contacts = array();
	foreach ($results as $id => $columns){
		$contacts[$id] = unserialize_vcard($columns['DATA']);
	}
	return $contacts;	
}

function read_assigned_contact(){
	global $user;
	$db = get_or_create_db();
	$query = $db->prepare('SELECT * FROM contacts WHERE id IN (SELECT contact_id FROM contacts_users WHERE user_id = :uid AND assigned = 1)');
	assert($query->execute(array(':uid'=>$user->id)),'Was not able to query contact for you!');
	$contacts = $query->fetchAll(INDEX_FETCH);
	$contact=reset($contacts);	
	$vcard= unserialize_vcard($contact['DATA']); 
	return $vcard;
}

function assign_contact($id){
	global $user;
	$contact = read_contacts($id);
	assert(!empty($contact),'No such contact or access to contact denied!');
	$contact = $contact[$id];
	$db = get_or_create_db();
	$query = $db->prepare('UPDATE contacts_users SET assigned = 0 WHERE user_id = :uid');
	assert($query->execute(array(':uid'=>$user->id)),'Was not able to un-assign contacts with user');
	$query = $db->prepare('UPDATE contacts_users SET assigned = 1 WHERE contact_id = :cid AND user_id = :uid');
	assert($query->execute(array(':cid'=>$id,':uid'=>$user->id)),'Was not able to assign contact with user');
}
?>