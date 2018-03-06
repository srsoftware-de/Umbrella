<?php

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
	function __construct($data){

		$fields = [
			1=>'post_box',
			2=>'ext_addr',
			3=>'street',
			4=>'locality',
			5=>'region',
			6=>'post_code',
			7=>'country'
		];
		if (is_array($data)){
			foreach ($fields as $index => $name) $this->{$name} = isset($data[$name]) ? $data[$name] : $data[$index];
		} else {
			$parts = explode(';',$data);
			foreach ($fields as $name) $this->{$name} = array_shift($parts);
		}
	}

	function __toString(){
		return trim($this->ext_addr . "\n" . $this->street . "\n" . $this->post_code . ' ' . $this->locality . "\n" . $this->region . "\n" . $this->country);
	}

	function get(){
		return str_replace("\n", ' / ',(string)$this);
	}
}

class Name{
	function __construct($data){
		$fields = [
			1 => 'family',
			2 => 'given',
			3 => 'additional',
			4 => 'prefixes',
			5 => 'suffixes'
		];

		if (is_array($data)){
			foreach ($fields as $index => $name) $this->{$name} = isset($data[$name]) ? $data[$name] : $data[$index];
		} else {
			$parts = explode(';',$data);
			foreach ($fields as $name) $this->{$name} = array_shift($parts);
		}
	}

	function __toString(){
		return trim(implode(';',[
			$this->family,
			$this->given,
			$this->additional,
			$this->prefixes,
			$this->suffixes
		]));
	}

	function beauty(){
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
		} else {
			$this->parse($data);
		}
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
		}
		if (!isset($this->dirty)) $this->dirty = [];

		foreach ($data as $key => $val){
			if ($key === 'id' && isset($this->id)) continue;
			switch ($key){
				case 'ADR':
					$val = new Address($val);
					break;
				case 'N':
					$val = new Name($val);
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

	function as_array(){
		$vcard = array();
		$vcard['BEGIN'] = 'VCARD';
		$vcard['VERSION'] = '4.0';
		foreach ($this as $key => $value) {
			if (in_array($key,['dirty','id'])) continue;
			if (is_array($value)){
				ksort($value); // die Werte vom Formular kommen nicht unbedingt in der durch den Index angezeigten Reihenfolge. Deshalb: nach index sortieren

				// Es kann sein, dass das Formular nicht für alle Felder, welche die VCard-Spezifikation vorsieht, Werte liefert.
				// Entsprechend werden alle nicht bedienten Felder mit leeren Werten belegt:
				$lastkey = array_pop(array_keys($value));
				if (is_numeric($lastkey)){
					for ($index = 1; $index<$lastkey; $index++) {
						if (!isset($value[$index])) $value[$index] = '';
					}
					ksort($value);
				}
				$value = implode(';', $value);
			}
			$value = str_replace(array("\r\n","\r","\n"), ';', $value);
			$vcard[$key] = $value;
		}
		$vcard['END'] = 'VCARD';
		return $vcard;
	}

	function __toString(){
		$arr = $this->as_array();
		$code = '';
		foreach ($arr as $key => $val) $code .= $key.':'.$val."\r\n";
		return $code;
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

	function name($beauty = false){
		if (isset($this->N)) return $beauty ? $this->N->beauty() : $this->N;
		return null;
	}

	function phones(){
		if (isset($this->TEL)){
			if (is_array($this->TEL)) return $this->TEL;
			return [ $this->TEL ];
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

	public function save(){
		global $user;

		$db = get_or_create_db();
		if (is_int($this->id)){
			$query  = $db->prepare('UPDATE contacts SET data=:data WHERE id = :id');
			assert($query->execute(array(':id'=>$this->id, ':data'=>(string)$this)),'Was not able to update contact!');
		} else {
			$query = $db->prepare('INSERT INTO contacts (data) VALUES (:data)');
			assert($query->execute(array(':data'=>(string)$this)),'Was not able to create new contact!');
			$this->id = $db->lastInsertId();
			$query =$db->prepare('INSERT INTO contacts_users (contact_id, user_id) VALUES (:cid, :uid)');
			assert($query->execute(array(':cid'=>$this->id,':uid'=>$user->id)),'Was not able to assign new contact to user');
		}
	}
}

?>
