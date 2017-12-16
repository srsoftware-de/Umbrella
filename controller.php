<?php

function get_or_create_db(){
	if (!file_exists('db')) assert(mkdir('db'),'Failed to create item/db directory!');
	assert(is_writable('db'),'Directory item/db not writable!');
	if (!file_exists('db/items.db')){
		$db = new PDO('sqlite:db/items.db');
		
		$tables = [
			'items'=>Item::table(),
		];
		
		foreach ($tables as $table => $fields){		
			$sql = 'CREATE TABLE '.$table.' ( ';
			foreach ($fields as $field => $props){
				$sql .= $field . ' ';
				if (is_array($props)){
					foreach ($props as $prop_k => $prop_v){
						switch (true){
							case $prop_k==='VARCHAR':
								$sql.= 'VARCHAR('.$prop_v.') '; break;
							case $prop_k==='DEFAULT':
								$sql.= 'DEFAULT '.($prop_v === null)?'NULL ':('"'.$prop_v.'" '); break;
							case $prop_k==='KEY':
								assert($prop_v === 'PRIMARY','Non-primary keys not implemented in invoice/controller.php!');
								$sql.= 'PRIMARY KEY '; break;
							default:
								$sql .= $prop_v.' ';
						}	
					}		
					$sql .= ", ";
				} else $sql .= $props.", ";
			}
			$sql = str_replace([' ,',', )'],[',',')'],$sql.')');
			$query = $db->prepare($sql);
			assert($db->query($sql),'Was not able to create items table in items.db!');
		}
	} else {
		$db = new PDO('sqlite:db/items.db');
	}
	return $db;

}

class Item{
	static function table(){
		return [
			'id'		=> ['INTEGER','KEY'=>'PRIMARY'],
			'company_id'	=> ['INT','NOT NULL'],
			'code'		=> ['VARCHAR'=>255],
			'name'		=> ['VARCHAR'=>255,'NOT NULL'],
			'description'	=> 'TEXT',
			'unit'		=> ['VARCHAR'=>64],
			'unit_price'	=> 'INT',
			'tax'		=> 'INT'
		];
	}
	
	static function load($options = array()){
		$templates = [];
		$db = get_or_create_db();

		$where = [];
		$args = [];
		$sql = 'SELECT id,* FROM items';

		$companies = request('company','json');
	
		
		if (isset($options['company_id'])){
			$cid = $options['company_id'];
			if (!array_key_exists($cid,$companies)){
				error("You requested items of a company you don't belong to!");
				return null;
			}
			$where[] = 'company_id = ?';
			$args[] = $cid;
		} else {
			$qMarks = str_repeat('?,', count($companies)-1).'?';
			$where[] = 'company_id IN ('.$qMarks.')';
			$args = array_merge($args, array_keys($companies));
		}
		if (isset($options['ids'])){
			$ids = $options['ids'];
			if (!is_array($ids)) $ids = [$ids];
			$qMarks = str_repeat('?,', count($ids)-1).'?';
			$where[] = 'id IN ('.$qMarks.')';
			$args = array_merge($args, $ids);
		}
		if (!empty($where)){
			$sql .= ' WHERE '.implode(' AND ',$where);
		}
		$query = $db->prepare($sql);
		assert($query->execute($args),'Was not able to load items for the selected company.');
		$rows = $query->fetchAll(INDEX_FETCH);
		if (empty($rows)){
			error('Was not able to load any items.');
			return null;
		}
		foreach ($rows as $row) {
			$item = new Item();
			$item->patch($row);
			$item->dirty = [];
			$item->company = $companies[$item->id];
			if (isset($options['single']) && $options['single']) return $item;
			$items[$item->id] = $item;
		}
		return $items;
	}
	
	function patch($data = array()){
		if (!isset($this->dirty)) $this->dirty = [];
		foreach ($data as $key => $val){
			if ($key === 'id' && isset($this->id)) continue;
			if (!isset($this->{$key}) || $this->{$key} != $val) $this->dirty[] = $key;
			$this->{$key} = $val;
		}
	}
	
	public function save(){
		global $user;
		$db = get_or_create_db();
		if (isset($this->id)){
			if (!empty($this->dirty)){
				$sql = 'UPDATE items SET';
				$args = [':id'=>$this->id];
				foreach ($this->dirty as $field){
					$sql .= ' '.$field.'=:'.$field.',';
					$args[':'.$field] = $this->{$field};
				}
				$sql = rtrim($sql,',').' WHERE id = :id';
				$query = $db->prepare($sql);
				assert($query->execute($args),'Was no able to update item in database!');
			}
		} else {
			$known_fields = array_keys(Item::table());
			$fields = [];
			$args = [];
			foreach ($known_fields as $f){
				if (isset($this->{$f})){
					$fields[]=$f;
					$args[':'.$f] = $this->{$f};
				}
			}
			$sql = 'INSERT INTO items ( '.implode(', ',$fields).' ) VALUES ( :'.implode(', :',$fields).' )';
			$query = $db->prepare($sql);
			assert($query->execute($args),'Was not able to insert new item');
			$this->id = $db->lastInsertId();
			$this->dirty = [];
		}
	}
	
	public function file(){
		$tempfile = tempnam('/tmp','template_');
		$f = fopen($tempfile,'w');
		fwrite($f,$this->template);
		fclose($f);
		return $tempfile;
	}
}
?>
