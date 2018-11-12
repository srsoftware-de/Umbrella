<?php
	include '../bootstrap.php';
	
	const MODULE = 'Stock';
	$title = 'Umbrella Stock Management';
	
	const TYPE_STRING = 0;
	const TYPE_INT    = 1;
	const TYPE_FLOAT  = 2;
	const TYPE_BOOL   = 3;
	
	$base_url = getUrl('stock');
	
	function get_or_create_db(){
		$table_filename = 'stock.db';
		if (!file_exists('db')) assert(mkdir('db'),'Failed to create '.strtolower(MODULE).'/db directory!');
		assert(is_writable('db'),'Directory '.strtolower(MODULE).'/db not writable!');
		if (!file_exists('db/'.$table_filename)){
			$db = new PDO('sqlite:db/'.$table_filename);
	
			$tables = [
				'items'=>Item::table(),
				'locations'=>Location::table(),
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
				assert($db->query($sql),'Was not able to create '.$table.' table in '.$table_filename.' ("'.$sql.'") !');
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
				'name'				=> ['TEXT'],
				'location_id'		=> ['INT'],
			];
		}
		
		function getNextId($prefix){
			$sql = 'SELECT id,code FROM items WHERE id LIKE :prefix';
			$db = get_or_create_db();
			$query = $db->prepare($sql);
			assert($query->execute([':prefix'=>$prefix.'%']),'Was not able to read item ids from database.');
			$rows = $query->fetchAll(PDO::FETCH_ASSOC);
			if (empty($rows)) return $prefix.'1';
			$row = array_pop($rows);
			
			$parts = explode(':',$row['id']);
			return $prefix.(array_pop($parts)+1);
		}
		
		static function load($options){
			global $user,$services;
		
			$sql = 'SELECT * FROM items';
		
			$where = [];
			$args =  [];
			$single = false;
			
			if (isset($options['search'])){
				$key = $options['search'];
				
				$companies = isset($services['company']) ? request('company','json') : null;
				
				$cond = '(id LIKE ?';
				$args[] = 'user:'.$user->id.':%';
				
				if (!empty($companies)){
					foreach ($companies as $cid => $dummy){
						$cond .= ' OR id LIKE ?';
						$args[] = 'company:'.$cid.':%';
					}
				}
				$cond .= ')';
				
				$where[] = $cond;
				
				
				$where[] = 'code LIKE ? OR name LIKE ?';
				$args[] = "%$key%";
				$args[] = "%$key%";
			} elseif (isset($options['ids'])){
				$ids = $options['ids'];
				if (!is_array($ids)){
					$single = true;
					$ids = [$ids];
				}
				$qmarks = implode(',', array_fill(0, count($ids), '?'));
				$where[] = 'id IN ('.$qmarks.')';
				$args = array_merge($args,$ids);
			} else {
				assert(isset($options['prefix']),'No Item Id Prefix set!');
				
				$prefix = $options['prefix'];
				
				$where[] = 'id LIKE ?';
				$args[] = $prefix.'%';
			}
			
			if (!empty($where)) $sql .= ' WHERE '.implode(' AND ',$where);
		
			$sql .= ' COLLATE NOCASE';
		
			if (isset($options['order'])){
				switch ($options['order']){
					case 'code':
					case 'id':
					case 'name':
						$sql.= ' ORDER BY '.$options['order'].' DESC';
						break;
					case 'location':
						$sql.= ' ORDER BY location_id';
						break;
				}
			} 
		
			$db = get_or_create_db();
			$query = $db->prepare($sql);
			//debug(query_insert($query, $args),1);
			assert($query->execute($args),'Was not able to request item type list!');
			$rows = $query->fetchAll(PDO::FETCH_ASSOC);
			$items = [];
		
			foreach ($rows as $row){
				$item = new Item();
				$item->patch($row);
				unset($item->dirty);
				if ($single) return $item;
				$items[$item->id] = $item;
			}
			if ($single) return reset($items);
			return $items;
		}
		
		static function loadCodes($prefix){
			$sql = 'SELECT code FROM items WHERE id LIKE :key GROUP BY code ORDER BY code COLLATE NOCASE';
			$db = get_or_create_db();
			$query = $db->prepare($sql);
			$args = [':key'=>$prefix.'%'];
			assert($query->execute($args),'Was not able to read item codes');
			return $query->fetchAll();
		}
		
		function location(){
			if (empty($this->location)) $this->location = Location::load(['ids'=>$this->location_id]);
			return $this->location;
		}
		
		function properties(){
			if (empty($this->properties)) $this->properties = ItemProperty::load(['item_id'=>$this->id]);
			return $this->properties;
		}
		
		function save(){
			$db = get_or_create_db();
			$args = [':id'=>$this->id,':code'=>$this->code,':name'=>$this->name,':loc'=>$this->location_id];
				
			$query = $db->prepare('INSERT OR IGNORE INTO items (id, code, name, location_id) VALUES (:id, :code, :name, :loc );');
			assert($query->execute($args),'Was not able to insert new entry into items table');

			$query = $db->prepare('UPDATE OR IGNORE items SET code = :code, name = :name, location_id = :loc WHERE id = :id ');
			assert($query->execute($args),'Was not able to update entry in item_types table');
				
			unset($this->dirty);
			return $this;
		}
	}
	
	class ItemProperty extends UmbrellaObject{
		static function table(){
			return [
				'item_id'			=> ['INT','NOT NULL'],
				'prop_id'			=> ['INT','NOT NULL'],
				'value'				=> ['VARCHAR'=>255,'NOT NULL'],
				'PRIMARY KEY'		=> ['item_id','prop_id']
			];
		}
		
		static function load($options){
			global $user;
		
			$sql = 'SELECT item_id, prop_id, value FROM item_props LEFT JOIN properties ON id=prop_id'; // join is done to allow ordering by name
		
			$where = [];
			$args =  [];
			$single = false;
		
			if (isset($options['item_id'])){
				$where[] = 'item_id = ?';
				$args[] = $options['item_id'];
			} else {
				assert(isset($options['prefix']),'No Item Code Prefix set!');
				if (isset($options['prefix'])){
					$where[] = 'item_id LIKE ?';
					$args[] = $options['prefix'].'%';
				}
			}
		
			if (!empty($where)) $sql .= ' WHERE '.implode(' AND ',$where);
		
			$sql .= ' COLLATE NOCASE';
		
			if (isset($options['order'])){
				$order = is_array($options['order']) ? $options['order'] : [$options['order']];
				$sql.= ' ORDER BY '.implode(', ',$order);
			} else {
				$sql.= ' ORDER BY name';
			}
			$db = get_or_create_db();
			$query = $db->prepare($sql);
			//debug(query_insert($query, $args),1);
			assert($query->execute($args),'Was not able to request item property list!');
			$rows = $query->fetchAll(PDO::FETCH_ASSOC);
			$props = [];
		
			foreach ($rows as $row){
				$prop = new ItemProperty();
				$prop->patch($row);
				unset($prop->dirty);
				if ($single) return $prop;
				$props[$prop->prop_id] = $prop;
			}
			if ($single) return reset($props);
			return $props;
		}
		
		function name(){
			return $this->property()->name;
		}
		
		function property(){
			if (empty($this->property)) $this->property = Property::load(['ids'=>$this->prop_id]);
			return $this->property;
		}
		
		function save(){
			$db = get_or_create_db();
			$args = [':item_id'=>$this->item_id,':prop_id'=>$this->property->id,':value'=>$this->value];
			
			$query = $db->prepare('INSERT OR IGNORE INTO item_props (item_id, prop_id, value) VALUES (:item_id, :prop_id, :value);');
			assert($query->execute($args),'Was not able to store property in database');
			
			$query = $db->prepare('UPDATE item_props SET value = :value WHERE item_id = :item_id AND prop_id = :prop_id');
			assert($query->execute($args),'Was not able to store property in database');
			unset($this->dirty);
			return $this;
		}
		
		function unit(){
			return $this->property()->unit;
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

			if (isset($options['ids'])){
				$ids = $options['ids'];
				if (!is_array($ids)){
					$single = true;
					$ids = [$ids];
				}
				$qmarks = implode(',', array_fill(0, count($ids), '?'));
				$where[] = 'id IN ('.$qmarks.')';
				$args = array_merge($args,$ids);
			} else {
				assert(isset($options['prefix']),'No Item Code Prefix set!');
				if (isset($options['prefix'])){
					$where[] = 'id LIKE ?';
					$args[] = $options['prefix'].'%';
				}
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
		
		function full(){
			if (!empty($this->location_id)) return $this->parent_loc()->full().' / '.$this->name;
			return $this->name;
		}
		
		function parent_loc(){
			if (empty($this->parent) && !empty($this->location_id)) $this->parent = Location::load(['ids'=>$this->location_id]);
			return $this->parent;
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
				'unit'				=> ['VARCHAR'=>255],
			];
		}
		
		static function getRelated($item){
			$sql = 'SELECT properties.id, properties.name, type, unit 
					FROM items 
						LEFT JOIN item_props ON item_id=items.id
						LEFT JOIN properties ON properties.id = prop_id
					WHERE items.id LIKE :prefix 
					  AND code = :code 
					  AND properties.id IS NOT NULL
					GROUP BY properties.name
					COLLATE NOCASE
					ORDER BY properties.name ASC';
			$db = get_or_create_db();
			$query = $db->prepare($sql);
			$parts = explode(':',$item->id);
			array_pop($parts);
			$args = [':code'=>$item->code,':prefix'=>implode(':', $parts).':%'];
			//debug(query_insert($query, $args),1);
			assert($query->execute($args),'Was not able to request related properties for '.$item_code);
			$rows = $query->fetchAll(PDO::FETCH_ASSOC);
			$props = [];
			foreach ($rows as $row){
				$property = new Property();
				$property->patch($row);
				$props[$property->id] = $property;
				unset($property->dirty);
			}
			return $props;
		}
		
		static function load($options = []){
			global $user;
		
			$sql = 'SELECT * FROM properties';
		
			$where = [];
			$args =  [];
			$single = false;
				
			if (isset($options['ids'])){
				$ids = $options['ids'];
				if (!is_array($ids)){
					$single = true;
					$ids = [$ids];
				}
				$qmarks = implode(',', array_fill(0, count($ids), '?'));
				$where[] = 'id IN ('.$qmarks.')';
				$args = array_merge($args,$ids);
			}	
			
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
			
			if (!empty($where)) $sql .= ' WHERE '.implode(' AND ',$where).' COLLATE NOCASE';
		
			if (isset($options['order'])){
				$order = is_array($options['order']) ? $options['order'] : [$options['order']];
				$sql.= ' ORDER BY '.implode(', ',$order);
			} else $sql .= ' ORDER BY name ASC';
		
			$db = get_or_create_db();
			$query = $db->prepare($sql);
			//debug(query_insert($query, $args),1);
			assert($query->execute($args),'Was not able to request property list!');
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
				$sql = 'INSERT INTO properties (name, type, unit) VALUES (:name, :type, :unit);';
				$args = [':name'=>$this->name, ':type'=>$this->type, ':unit'=>$this->unit];
				$query = $db->prepare($sql);
				assert($query->execute($args),'Was not able to save note!');
				$this->id = $db->lastInsertId();
				unset($this->dirty);
			}
		}
	}
?>
