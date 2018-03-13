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
	
	static $index = 0;
	
	function __construct($data, $param = null){
		if (is_array($data)){
			foreach (static::fields as $index => $name) {
				if (isset($data[$name])) {
					$this->{$name} = $data[$name];
				} elseif (isset($data[$index])) {
					$this->{$name} = $data[$index];
				} else {
					$this->{$name} = '';
				}
			}
		} else {
			$parts = explode(';',$data);
			foreach (static::fields as $name) $this->{$name} = array_shift($parts);
		}

		if (is_array($param))$this->param = $param;
	}

	function editFields(){
		$result  = "<fieldset>\n";
		$result .= '<legend>'.t('Address')."</legend>\n";
		foreach (Address::fields as $field) $result .= t($field).' <input type="text" name="ADR#'.Address::$index.'['.$field.']" value="'.$this->{$field}.'" />'."\n";
		
		$index = -1;
		$common_types = ['home'=>true,'work'=>true];

		if (isset($this->param['TYPE'])){
			 $types = $this->param['TYPE'];
			 if (!is_array($types)) $types = [$types];
			 foreach ($types as $index => $type){
			 	$result .= '<label><input type="checkbox" name="ADR#'.Address::$index.'[param][TYPE]['.$index.']" value="'.$type.'" checked="true"/> '.t($type).'</label><br/>';
			 	unset($common_types[$type]);
			 }
		}
		
		foreach ($common_types as $type => $dummy){
			$index++;
			$result .= '<label><input type="checkbox" name="ADR#'.Address::$index.'[param][TYPE]['.$index.']" value="'.$type.'" /> '.t($type).'</label><br/>';
		}
		
		$result .= "</fieldset>\n";
		Address::$index++;
		return $result;
	}
	
	function format($separator = null){
		if ($separator) return str_replace("\n",$separator,$this->__toString());
		 
		$str  = 'ADR';
		if (isset($this->param) && is_array($this->param)){			
			foreach ($this->param as $key => $val) {
				if (is_array($val)){
					foreach ($val as $v) $str .= ';'.$key.'='.$v;					
				} else $str .= ';'.$key.'='.$val;
			}
		}		
		return $str . ':'.implode(';',[$this->post_box,$this->ext_addr,$this->street,$this->locality,$this->region,$this->post_code,$this->country]);
	}
	
	static function in($data = array()){
		foreach (Address::fields as $field){
			if (array_key_exists($field, $data)) return true;
		}
		return false;
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
	
	static $index = 0;
	
	function __construct($key, $val, $param = null){
		$this->key = $key;
		$this->val = $val;
		$this->param = $param;
	}
	
	function editFields(){
		$result  = "<fieldset>\n";
		$result .= '<legend>'.t($this->key)."</legend>\n";
		$result .= '<input type="text" name="'.$this->key.'#'.GenericField::$index.'[val]" value="'.$this->val.'" />'."\n";
		
		$index = -1;
		$common_types = ['home'=>true,'work'=>true];
		
		if (isset($this->param['TYPE'])){
			 $types = $this->param['TYPE'];
			 if (!is_array($types)) $types = [$types];
			 
			 foreach ($types as $index => $type){
			 	$result .= '<label><input type="checkbox" name="'.$this->key.'#'.GenericField::$index.'[param][TYPE]['.$index.']" value="'.$type.'" checked="true"/> '.t($type).'</label><br/>';
			 	unset($common_types[$type]);
			 }
		}
		
		foreach ($common_types as $type => $dummy){
			$index++;
			$result .= '<label><input type="checkbox" name="'.$this->key.'#'.GenericField::$index.'[param][TYPE]['.$index.']" value="'.$type.'" /> '.t($type).'</label><br/>';
		}
		
		$result .= "</fieldset>\n";
		GenericField::$index++;
		return $result;
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
	
	function editFields(){
		$result  = "<fieldset>\n";
		$result .= '<legend>'.t('Name')."</legend>\n";
		foreach (Name::fields as $field) $result .= t($field).' <input type="text" name="N['.$field.']" value="'.$this->{$field}.'" />'."\n";
		$result .= "</fieldset>\n";
		return $result;
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
			$this->patch($data,true);
		} else $this->parse($data);
	}
	
	static function load($options = []){
		global $user;
		$db = get_or_create_db();
		$single = false;
		
		$subquery = 'SELECT contact_id FROM contacts_users WHERE user_id = ?';
		$args = [$user->id];
		
		if (isset($options['assigned']) && $options['assigned'] == true) {
			$subquery .= ' AND assigned = 1';
			$single = true;
		}
		
		$sql = 'SELECT * FROM contacts WHERE id IN ('.$subquery.')';
		
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
		
		if (isset($options['key'])){
			$sql .= ' AND DATA like ?';
			$args[] = '%'.$options['key'].'%';
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
	
	function assign_with_current_user(){
		global $user;
		$db = get_or_create_db();
		$query = $db->prepare('UPDATE contacts_users SET assigned = 0 WHERE user_id = :uid');
		assert($query->execute(array(':uid'=>$user->id)),'Was not able to un-assign contacts with user');
		$query = $db->prepare('UPDATE contacts_users SET assigned = 1 WHERE contact_id = :cid AND user_id = :uid');
		assert($query->execute(array(':cid'=>$this->id,':uid'=>$user->id)),'Was not able to assign contact with user');
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
		$param = null;
			
		if (!is_array($data)){
			$data = trim($data);
			if ($data == '') return;
			
			$main_parts = explode(':',trim($data),2); // ADR#1;TYPE=work:A;B;C;D;E => [ 'ADR#1;TYPE=work', 'A;B;C;D;E' ] // the dot in ADR#1 max result from a form
			$val = $main_parts[1]; // 'A;B;C;D;E'

			$key_parts = explode(';',$main_parts[0]); // ADR#1;TYPE=work => [ 'ADR#1', 'TYPE=work' ]
			$key = array_shift($key_parts); // ADR#1
			
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
			$key = explode('#',$key,2)[0]; // ADR#1 => ADR
			
			if ($key === 'id' && isset($this->id)) continue;
				
			if (isset($val['param'])) {
				$param = $val['param'];
				unset($val['param']);
			}
			if (isset($val['val']))	$val = $val['val'];

			switch ($key){
				case 'ADR':
					$val = new Address($val,$param);
					break;
				case 'EMAIL':
				case 'TEL':
					// these can hyve params like TYPE
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
		$this->dirty = array_unique($this->dirty);
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
