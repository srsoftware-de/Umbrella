<?php

	const MODULE = 'Stock';
	$title = 'Umbrella Stock Management';
		
	function get_or_create_db(){
		$table_filename = 'stock.db';
		if (!file_exists('db')) assert(mkdir('db'),'Failed to create '.strtolower(MODULE).'/db directory!');
		assert(is_writable('db'),'Directory '.strtolower(MODULE).'/db not writable!');
		if (!file_exists('db/'.$table_filename)){
			$db = new PDO('sqlite:db/'.$table_filename);
	
			$tables = [
				'items'=>Item::table(),
				'locations'=>Location::table(),
				'item_types'=>ItemType::table(),
				'item_props'=>ItemProperty::table(),
				'properties'=>Property::table(),
			];
	
			foreach ($tables as $table => $fields){
				$sql = 'CREATE TABLE '.$table.' ( ';
				foreach ($fields as $field => $props){
					if ($field == 'UNIQUE'||$field == 'PRIMARY KEY') {
						$field .='('.implode(',',$props).')';
						$props = null;
					}
					$sql .= $field . ' ';
					if (is_array($props)){
						foreach ($props as $prop_k => $prop_v){
							switch (true){
								case $prop_k==='VARCHAR':
									$sql.= 'VARCHAR('.$prop_v.') '; break;
								case $prop_k==='DEFAULT':
									$sql.= 'DEFAULT '.($prop_v === null?'NULL ':'"'.$prop_v.'" '); break;
								case $prop_k==='KEY':
									assert($prop_v === 'PRIMARY','Non-primary keys not implemented in '.strtolower(MODULE).'/controller.php!');
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
				assert($db->query($sql),'Was not able to create '.$table.' table in '.$table_filename.'!');
			}
		} else {
			$db = new PDO('sqlite:db/'.$table_filename);
		}
		return $db;
	}
	
	class Item extends UmbrellaObject{
		static function table(){
			return [
				'id'				=> ['VARCHAR'=>255,'KEY'=>'PRIMARY'],
				'code'				=> ['VARCHAR'=>255,'NOT NULL'],
				'location_id'		=> ['INT','NOT NULL'],
			];
		}
		
		function getNextId($prefix){
			$sql = 'SELECT id FROM items WHERE id LIKE :prefix ORDER BY id DESC LIMIT 1';
			$db = get_or_create_db();
			$query = $db->prepare($sql);
			assert($query->execute([':prefix'=>$prefix.'%']),'Was not able to read item ids from database.');
			$rows = $query->fetchAll(PDO::FETCH_ASSOC);
			if (empty($rows)) return $prefix.'1';
			$row = reset($rows);
			$parts = explode(':',$row['id']);
			return $prefix.(array_pop($parts)+1);
		}
		
		function save(){
			$db = get_or_create_db();
			$args = [':id'=>$this->id,':code'=>$this->code,':loc'=>$this->location->id];
				
			$query = $db->prepare('INSERT OR IGNORE INTO items (id, code, location_id) VALUES (:id, :code, :loc );');
			assert($query->execute($args),'Was not able to insert new entry into items table');

			$query = $db->prepare('UPDATE OR IGNORE items SET code = :code, location_id = :loc WHERE id = :id ');
			assert($query->execute($args),'Was not able to update entry in item_types table');
				
			unset($this->dirty);
			return $this;
		}
	}
	
	class ItemProperty{
		static function table(){
			return [
				'item_id'			=> ['INT','NOT NULL'],
				'property'			=> ['INT','NOT NULL'],
				'value'				=> ['VARCHAR'=>255,'NOT NULL'],
				'PRIMARY KEY'		=> ['item_id','property']
			];
		}
	}
	
	class ItemType extends UmbrellaObject{
		static function table(){
			return [
				'code'				=> ['VARCHAR'=>255,'NOT NULL'],
				'property'			=> ['INT','NOT NULL'],
				'value'				=> ['VARCHAR'=>255,'NOT NULL'],
				'PRIMARY KEY'		=> ['code','property']
			];
		}
		
		static function load($options){
			global $user;
				
			$sql = 'SELECT * FROM item_types';
				
			$where = [];
			$args =  [];
			$single = false;
			
			assert(isset($options['prefix']),'No Item Code Prefix set!');
			
			$prefix = $options['prefix'];
				
			if (isset($options['code'])){
				$code = $options['code'];
				if (!is_array($code)){
					$single = true;
					$code = [$code];
				}
				$qmarks = implode(',', array_fill(0, count($code), '?'));
				$where[] = 'code IN ('.$qmarks.')';
				foreach ($code as $c) $args[] = $prefix.$c;
			} else {
				$where[] = 'code LIKE ?';
				$args[] = $prefix.'%';
			}
			
				
			if (!empty($where)) $sql .= ' WHERE '.implode(' AND ',$where);
				
			$sql .= ' COLLATE NOCASE';
		
			if (isset($options['order'])){
				$order = is_array($options['order']) ? $options['order'] : [$options['order']];
				$sql.= ' ORDER BY '.implode(', ',$order);
			}
				
			$db = get_or_create_db();
			$query = $db->prepare($sql);
			//debug(query_insert($query, $args),1);
			assert($query->execute($args),'Was not able to request item type list!');
			$rows = $query->fetchAll(PDO::FETCH_ASSOC);
			$types = [];
		
			warn('Loading of properties not fully implemented');
			foreach ($rows as $row){
			}
			if ($single) return reset($types);
			return $types;
		}
		
		function save(){
			$db = get_or_create_db();
			$args = [':code'=>$this->code,':prop'=>$this->property->id,':value'=>$this->value];
			
			$query = $db->prepare('INSERT OR IGNORE INTO item_types (code, property, value) VALUES (:code, :prop, :value );');
			//debug(query_insert($query, $args));
			assert($query->execute($args),'Was not able to insert new entry into item_types table');
			
			$query = $db->prepare('UPDATE item_types SET value = :value WHERE code = :code AND property = :prop');
			assert($query->execute($args),'Was not able to update entry in item_types table');
					
			unset($this->dirty);
			return $this;
		}
	}
	
	class Location extends UmbrellaObject{
		static function table(){
			return [
				'id'				=> ['VARCHAR'=>255,'KEY'=>'PRIMARY'],
				'location_id'		=> ['INT','DEFAULT'=>0],
				'name'				=> ['VARCHAR'=>255,'NOT NULL'],
				'description'		=> ['TEXT'],
			];
		}
		
		static function load($options){
			global $user;
		
			$sql = 'SELECT * FROM locations';
		
			$where = [];
			$args =  [];
			$single = false;
				
			assert(isset($options['prefix']),'No Item Code Prefix set!');
				
			if (isset($options['prefix'])){
				$where[] = 'id LIKE ?';
				$args[] = $options['prefix'].'%';
			}
		
			if (!empty($where)) $sql .= ' WHERE '.implode(' AND ',$where);
		
			$sql .= ' COLLATE NOCASE';
		
			if (isset($options['order'])){
				$order = is_array($options['order']) ? $options['order'] : [$options['order']];
				$sql.= ' ORDER BY '.implode(', ',$order);
			}
		
			$db = get_or_create_db();
			$query = $db->prepare($sql);
			//debug(query_insert($query, $args),1);
			assert($query->execute($args),'Was not able to request locations list!');
			$rows = $query->fetchAll(PDO::FETCH_ASSOC);
			$locations = [];
		
			foreach ($rows as $row){
				$location = new Location();
				$location->patch($row);
				unset($location->dirty);
				$locations[$location->id] = $location;
			}
			if ($single) return reset($locations);
			return $locations;
		}
		
		function save(){
			$db = get_or_create_db();
			$args = [':location_id'=>null,':description'=>null,':name'=>null];
			foreach ($this as $field => $value) {
				if (is_array($value)) continue;
				$args[':'.$field] = $value;
			}
			if (isset($this->id)){
				$query = $db->prepare('UPDATE locations SET location_id = :location_id, name = :name, description = :description WHERE id = :id');
			} elseif (isset($this->new_id)){
				$query = $db->prepare('INSERT OR IGNORE INTO locations (id, location_id, name, description) VALUES (:new_id, :location_id, :name, :description );');
				
			} else throw new Exception('Can not save location: Neither new_id nor id set!');
			
			assert($query->execute($args),'Was not able to store location in database');
			unset($this->dirty);
			return $this;
		}
	}

	class Property extends UmbrellaObjectWithId{
		static function table(){
			return [
				'id'				=> ['INTEGER','KEY'=>'PRIMARY'],
				'name'				=> ['VARCHAR'=>255,'NOT NULL'],
				'type'				=> ['INT','NOT NULL'],
			];
		}
		
		static function load($options){
			global $user;
		
			$sql = 'SELECT * FROM properties';
		
			$where = [];
			$args =  [];
			$single = false;
				
			if (isset($options['name'])){
				$names = $options['name'];
				if (!is_array($names)){
					$single = true;
					$names = [$names];
				}
				$qmarks = implode(',', array_fill(0, count($names), '?'));
				$where[] = 'name IN ('.$qmarks.')';
				$args = array_merge($args,$names);
			}	
		
			if (!empty($where)) $sql .= ' WHERE '.implode(' AND ',$where);
		
			$sql .= ' COLLATE NOCASE';
		
			if (isset($options['order'])){
				$order = is_array($options['order']) ? $options['order'] : [$options['order']];
				$sql.= ' ORDER BY '.implode(', ',$order);
			}
		
			$db = get_or_create_db();
			$query = $db->prepare($sql);
			assert($query->execute($args),'Was not able to request item type list!');
			$rows = $query->fetchAll(PDO::FETCH_ASSOC);
			$properties = [];

			foreach ($rows as $row){
				$property = new Property();
				$property->patch($row);
				unset($property->dirty);
				if ($single) return $property;
				$properties[$property->id] = $property;
			}
			if ($single) return reset($properties);
			return $properties;
		}
		
		function save(){
			global $user;
			$db = get_or_create_db();
		
			if (isset($this->id)){
				if (!empty($this->dirty)){
					$sql = 'UPDATE properties SET';
					$args = [':id'=>$this->id];
					foreach ($this->dirty as $field){
						$sql .= ' '.$field.'=:'.$field.',';
						$args[':'.$field] = $this->{$field};
					}
					$sql = rtrim($sql,',').' WHERE id = :id';
					$query = $db->prepare($sql);
					assert($query->execute($args),'Was no able to update property in database!');
				}
			} else {
				$sql = 'INSERT INTO properties (name, type) VALUES (:name, :type);';
				$args = [':name'=>$this->name, ':type'=>$this->type];
				$query = $db->prepare($sql);
				assert($query->execute($args),'Was not able to save note!');
				$this->id = $db->lastInsertId();
				unset($this->dirty);
			}
		}
	}
?>
