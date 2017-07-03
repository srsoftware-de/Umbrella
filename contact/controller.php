<?php 

if (!isset($services['files'])) die('Contact service requres file service to be active!');
	
function get_or_create_db(){
	if (!file_exists('db')){
		assert(mkdir('db'),'Failed to create contact/db directory!');
	}
	assert(is_writable('db'),'Directory contact/db not writable!');
	if (!file_exists('db/contacts.db')){
		$db = new PDO('sqlite:db/contacts.db');
		$db->query('CREATE TABLE contacts (id INTEGER PRIMARY KEY, DATA TEXT);');
		$db->query('CREATE TABLE contacts_users (contact_id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY(contact_id, user_id));');
	} else {
		$db = new PDO('sqlite:db/contacts.db');
	}
	return $db;
}

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

function store_vcard($vcard = null, $id = null){
	global $user;
	assert(is_array($vcard),'No vcard data passsed to store_vcard');
	$data = serialize_vcard($vcard);
	debug($data);
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

function serialize_vcard($vcard){
	$result = '';
	foreach ($vcard as $key => $value){
		if (is_array($value)){
			$index = 0;
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
		if (trim($value) == '') continue;
		$result .= $key.':'.$value."\r\n";
	}
	return $result;	
}

function unserialize_vcard($raw){
	$lines = explode("\r\n", $raw);
	$vcard = array();	
	foreach ($lines as $line){
		if (empty($line)) continue;		
		$map = explode(':', $line,2);
		$key = $map[0];
		$val = $map[1];
		$vcard[$key]=$val; 
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
?>