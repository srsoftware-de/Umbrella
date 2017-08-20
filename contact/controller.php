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
	foreach ($data as $entry_type => $value) {
		if (is_array($value)){ // either multipart-value, params given, or multiple entries
			//debug('Entry "'.$entry_type.'" contains ARRAY.');
			
			foreach ($value as $first_key => $dummy) break;
			
			if (strpos($first_key, '=') !== false){ // parametric entry!
				//debug('The array contains entry type parameters (first key is "'.$first_key.'").');
				foreach ($value as $k => $v){
					$typed_key = $entry_type.';'.$k;
					$vcard[$typed_key] = array();
					//debug('Precessing data for '.$typed_key);
					
					if (is_array($v)){
						//debug('Multiple entries found!');
						foreach ($v as $entry){
							$vcard[$typed_key][]=convert_val($entry_type, $entry);
						}
						continue;
					} 
					//debug('Processing single entry');
					$vcard[$typed_key]=convert_val($entry_type, $v);
				}
				continue;
			}
			
			
			
			if (is_numeric($first_key)){ // Multiple entries!
				//debug('That\'s just multiple entries. Passing data.');
				$vcard[$entry_type] = $value;
				continue;
			}
			
			
			// neither parametric nor multiple entries, must be multi-part			
			//debug('This is a multipart entry (first key is "'.$first_key.'")! Processing.');
			$vcard[$entry_type]=convert_val($entry_type, $value);
		} else {			
			$vcard[$entry_type] = convert_val($entry_type,$value);	
		}
	}
	//debug($vcard,1);
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
			foreach ($value as $val){
				$result .= $key.':'.$val."\r\n";
			}
			continue;
		}
		if (trim($value) == '') continue;
		$result .= $key.':'.$value."\r\n";
	}
	return $result;	
}

function unserialize_vcard($raw){
	$raw = str_replace("\r\n","\n",$raw);
	$lines = explode("\n", $raw);
	$vcard = array();	
	foreach ($lines as $line){
		if (empty($line)) continue;	
		
		$map = explode(':', $line,2);
		$key = $map[0];
		$val = $map[1];
		
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