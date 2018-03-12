<?php

// all classes: __toString() creates human-readable string
// all classes: __format() creates VCF-formatted string

const CRLF = "\r\n";
const MULTILINE=true;
const BEAUTY = true;

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

function read_assigned_contact(){
	global $user;
	$db = get_or_create_db();
	$query = $db->prepare('SELECT * FROM contacts WHERE id IN (SELECT contact_id FROM contacts_users WHERE user_id = :uid AND assigned = 1)');
	assert($query->execute(array(':uid'=>$user->id)),'Was not able to query contact for you!');
	$contacts = $query->fetchAll(INDEX_FETCH);
	$contact=reset($contacts);
	if ($contact) return unserialize_vcard($contact['DATA']); 
	return null;
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

class Address{
	const fields = [
		1=>'post_box',
		2=>'ext_addr',
		3=>'street',
		4=>'locality',
		5=>'region',
		6=>'post_code',
		7=>'country'
	];
	
	function __construct($data, $param = null){
		if (is_array($data)){
			foreach (static::fields as $index => $name) $this->{$name} = isset($data[$name]) ? $data[$name] : $data[$index];
		} else {
			$parts = explode(';',$data);
			foreach (static::fields as $name) $this->{$name} = array_shift($parts);
		}

		if (is_array($param))$this->param = $param;
	}
	
	function format($separator = null){
		if ($separator) return str_replace("\n",$separator,$this->__toString());
		 
		$str  = 'ADR';
		if (is_array($this->param)){			
			foreach ($this->param as $key => $val) {
				if (is_array($val)){
					foreach ($val as $v) $str .= ';'.$key.'='.$v;					
				} else $str .= ';'.$key.'='.$val;
			}
		}		
		return $str . ':'.implode(';',[$this->post_box,$this->ext_addr,$this->street,$this->locality,$this->region,$this->post_code,$this->country]);
	}

	function __toString(){
		$str = '';
		foreach (Address::fields as $field){
			if (isset($this->{$field}) && $this->{$field} !== null && $this->{$field} != '') $str .= $this->{$field}."\n";
		}
		
		$type = '';
		if (isset($this->param['TYPE'])){
			if (is_array($this->param['TYPE'])){
				$type = '('.implode(',',$this->param['TYPE']).')';
			} else $type = '('.$this->param['TYPE'].')';
		}
		return trim(trim($str).' '.$type);
	}
}

class GenericField{
	function __construct($key, $val, $param = null){
		$this->key = $key;
		$this->val = $val;
		$this->param = $param;
	}

	function format(){
		$str  = $this->key;
		if (is_array($this->param)){
			foreach ($this->param as $key => $val) {
				if (is_array($val)){
					foreach ($val as $v) $str .= ';'.$key.'='.$v;
				} else $str .= ';'.$key.'='.$val;
			}
		}
		return $str.':'.trim($this->val);
	}
	
	function __toString(){
		$type = '';
		if (isset($this->param['TYPE'])){
			if (is_array($this->param['TYPE'])){
				$type = '('.implode(',',$this->param['TYPE']).')';
			} else $type = '('.$this->param['TYPE'].')';
		}
		return trim($this->val.' '.$type);
	}
}

class Name{
	const fields = [
		1 => 'family',
		2 => 'given',
		3 => 'additional',
		4 => 'prefix',
		5 => 'suffix'
	];
	function __construct($data,$param = null){
		

		if (is_array($data)){
			foreach (static::fields as $index => $name) $this->{$name} = isset($data[$name]) ? $data[$name] : $data[$index];
		} else {
			$parts = explode(';',$data);
			foreach (static::fields as $name) $this->{$name} = array_shift($parts);
		}

		if (is_array($param))$this->param = $param;
	}

	function format(){
		return 'N:'.trim(implode(';',[
			$this->family,
			$this->given,
			$this->additional,
			$this->prefix,
			$this->suffix
		]));
	}

	function __toString(){
		return $this->given.' '.$this->family;
	}
}



class VCard{

	/*
	 * VCard constructor only stores VCard-as-string
	 * Any work on members is done in separate methods
	 */

	function __construct($data){
		if (is_array($data)){
			$this->patch($data);
		} else $this->parse($data);
	}
	
	static function load($options = []){
		global $user;
		$db = get_or_create_db();
		$sql = 'SELECT * FROM contacts WHERE id IN (SELECT contact_id FROM contacts_users WHERE user_id = ?)';
		$args = [$user->id];
	
		$single = false;
	
		if (isset($options['ids'])){
			$ids = $options['ids'];
			if (!is_array($ids)) {
				$ids = [$ids];
				$single = true;
			}
			$qMarks = str_repeat('?,', count($ids)-1).'?';
			$sql .= ' AND id IN ('.$qMarks.')';
			$args = array_merge($args,$ids);
		}
	
		$query = $db->prepare($sql);
		assert($query->execute($args),'Was not able to query contacts for you!');
		$rows = $query->fetchAll(INDEX_FETCH);
		$cards = [];
		foreach ($rows as $id => $columns){
			$card = new VCard($columns['DATA']);
			unset($card->dirty);
			$card->id = $id;
			if ($single) return $card;
			$cards[$id] = $card;
		}
		return $cards;
	}
	
	function addresses(){
		if (isset($this->ADR)) {
			if (is_array($this->ADR)) return $this->ADR;
			return [ $this->ADR ];
		}
		return [];
	}
	
	function emails(){
		if (isset($this->EMAIL)){
			if (is_array($this->EMAIL)) return $this->EMAIL;
			return [ $this->EMAIL ];
		}
		return [];
	}
	
	function format(){
		$code  = 'BEGIN:VCARD'.CRLF;
		$code .= 'VERSION:3.0'.CRLF;
		$code .= 'PRODID:Umbrella Contact manager by Keawe'.CRLF;
	
		foreach ($this as $key => $value){
			if (in_array($key,['dirty','id'])) continue;
			if (is_array($value)){
				foreach ($value as $val){
					$code .= (is_object($val) ? $val->format() : $key.':'.str_replace(array(CRLF,"\r","\n"), '\n', $val)).CRLF;
				}
			} else	$code .= (is_object($value) ? $value->format() : $key.':'.str_replace(array(CRLF,"\r","\n"), '\n', $value)).CRLF;
		}
		$code .= 'END:VCARD'.CRLF;
		return $code;
	}
	
	function name($beauty = false){
		if (isset($this->N)) return $beauty ? (string)$this->N : $this->N;
		return null;
	}

	function parse($code){
		// remove in-line line breaks
		$code = str_replace(["\r\n ","\r\n\t"],"",$code);

		$lines = explode("\r\n",$code);
		foreach ($lines as $line) $this->patch($line,true);
	}

	function patch($data = array(), $add = false){
		if (!is_array($data)){
			$main_parts = explode(':',trim($data),2);
			$val = $main_parts[1];

			$key_parts = explode(';',$main_parts[0]);
			$key = array_shift($key_parts);

			switch ($key){
				case '':
				case 'BEGIN':
				case 'END':
				case 'VERSION':
				case 'PRODID':
					return;
					break;
			}
			$data = [$key => $val];
			$param = null;
			if (count($key_parts)) {
				foreach ($key_parts as $part){
					$dummy = explode('=',$part,2);
					$k = array_shift($dummy);
					$v = array_shift($dummy);
					if (isset($param[$k])){
						if (is_array($param[$k])){
							$param[$k][] = $v;
						} else {
							$param[$k] = [$param[$k], $v];
						}
					} else {
						$param[$k] = $v;
					}
				}
			}
		}
		if (!isset($this->dirty)) $this->dirty = [];

		foreach ($data as $key => $val){
			if ($key === 'id' && isset($this->id)) continue;
			switch ($key){
				case 'ADR':
					$val = new Address($val,$param);
					break;
				case 'EMAIL':
				case 'TEL':
					$val = new GenericField($key,$val,$param);
					break;
				case 'N':
					$val = new Name($val,$param);
					break;
			}

			if (isset($this->{$key})){
				if ($add){ // add values
					if (is_array($this->{$key})){
						$this->{$key}[] = $val; // add new element to existing array
					} else $this->{$key} = [ $this->{$key}, $val]; // convert to array and add new element
				} else $this->{$key} = $val; // replace values
			} else $this->{$key} = $val; // create new field
			$this->dirty[] = $key;
		}
	}

	function phones(){
		if (isset($this->TEL)){
			if (is_array($this->TEL)) return $this->TEL;
			return [ $this->TEL ];
		}
		return [];
	}

	public function save(){
		global $user;

		$db = get_or_create_db();
		if (is_int($this->id)){
			$query  = $db->prepare('UPDATE contacts SET data=:data WHERE id = :id');
			assert($query->execute(array(':id'=>$this->id, ':data'=>$this->format())),'Was not able to update contact!');
		} else {
			$query = $db->prepare('INSERT INTO contacts (data) VALUES (:data)');
			assert($query->execute(array(':data'=>$this->format())),'Was not able to create new contact!');
			$this->id = $db->lastInsertId();
			$query =$db->prepare('INSERT INTO contacts_users (contact_id, user_id) VALUES (:cid, :uid)');
			assert($query->execute(array(':cid'=>$this->id,':uid'=>$user->id)),'Was not able to assign new contact to user');
		}
	}
}

?>
