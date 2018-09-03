<?php include '../bootstrap.php';

const MODULE = 'Bookmark';
$title = 'Umbrella Bookmark Management';

warn('The RTC module is currently under development.');
warn('Most functions will not work at the moment.');

function get_or_create_db(){
	$table_filename = 'rtc.db';
	if (!file_exists('db')) assert(mkdir('db'),'Failed to create rtp/db directory!');
	assert(is_writable('db'),'Directory rtc/db not writable!');
	if (!file_exists('db/'.$table_filename)){
		$db = new PDO('sqlite:db/'.$table_filename);

		$tables = [
			'channels'=>Channel::table(),
			'channels_users'=>Channel::users_table(),
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
								$sql.= 'DEFAULT '.($prop_v === null)?'NULL ':('"'.$prop_v.'" '); break;
							case $prop_k==='KEY':
								assert($prop_v === 'PRIMARY','Non-primary keys not implemented in bookmark/controller.php!');
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

class Channel extends UmbrellaObjectWithId{
	static function table(){
		return [
			'id'				=> ['INTEGER','KEY'=>'PRIMARY'],
			'hash'				=> ['VARCHAR'=>255,'NOT NULL'],
			'subject'			=> ['TEXT'],
		];
	}
	
	static function users_table(){
		return [
			'channel_id'	=> ['INT','NOT NULL'],
			'user_id'		=> ['INT','NOT NULL'],
			'UNIQUE'		=> ['channel_id','user_id'],
		];
	}
	
	static function load($options = []){
		global $services,$user;
		$sql = 'SELECT * FROM channels LEFT JOIN channels_users ON channels.id = channels_users.channel_id';
		$where = ['user_id = ?'];
		$args = [$user->id];
		$single = false;
		if (isset($options['search'])){
			$where[] = 'subject LIKE ?';
			$args[] = '%'.$options['search'].'%';
		}
		
		$single = false;
		if (isset($options['ids'])){
			$ids = $options['ids'];			
			if (!is_array($ids)) {
				$ids = [$ids];
				$single = true;
			}
			$qMarks = str_repeat('?,', count($ids)-1).'?';
			$where[] ='id IN ('.$qMarks.')';
			$args = array_merge($args, $ids);
		}
		
		if (!empty($where)) $sql .= ' WHERE '.implode(' AND ',$where);
			
		if (isset($options['order'])){
			$order = is_array($options['order']) ? $options['order'] : [$options['order']];
			$sql.= ' ORDER BY '.implode(', ',$order);
		}
		
		if (isset($options['limit'])){
			$sql.= ' LIMIT ?';
			$args[] = $options['limit'];
		}
		
		//debug(query_insert($sql,$args));
		$db = get_or_create_db();
		$query = $db->prepare($sql);
		//debug(query_insert($query,$args),1);
		assert($query->execute($args),'Was not able to request channel list!');
		$rows = $query->fetchAll(PDO::FETCH_ASSOC);
		$channels = [];
		foreach ($rows as $row){
			$c = new Channel();
			unset($row['user_id']);
			$c->patch($row);
			
			unset($c->dirty);
			
			if ($single) return $c;
			$channels[$row['id']] = $c;
		}
		if ($single) return null;
		
		$qMarks = empty($channels)?'':'?'.str_repeat(',?', count($channels)-1);
		$args = array_keys($channels);
		$sql = 'SELECT * FROM channels_users WHERE channel_id IN ('.$qMarks.')';
		$query = $db->prepare($sql);
		assert($query->execute($args),'Was not able to request channel user list!');
		$rows = $query->fetchAll(PDO::FETCH_ASSOC);
		foreach ($rows as $row){
			$cid = $row['channel_id'];
			$channels[$cid]->users[] = $row['user_id'];
		}
		return $channels;
	}
	
	function open(){
		redirect('https://meet.jit.si/'.$this->hash);
	}
	
	function save(){
		global $services,$user;
		$db = get_or_create_db();
		$known_fields = array_keys(Channel::table());
		if (isset($this->id)){
			
			$sql = 'UPDATE channels SET';
			$args = [];
			
			foreach ($this->dirty as $field){
				if (in_array($field, $known_fields)){
					$sql .= ' '.$field.'=:'.$field.',';
					$args[':'.$field] = $this->{$field};
				}
			}
			
			if (!empty($args)){
				$sql = rtrim($sql,',').' WHERE id = :id';
				$args[':id'] = $this->id;
				$query = $db->prepare($sql);
				assert($query->execute($args),'Was no able to update channel in database!');
			}
		} else {
			if (empty($this->hash)) $this->patch(['hash'=>md5($this->subject.time())]);
			
			$fields = [];
			$args = [];
			foreach ($known_fields as $f){
				if (isset($this->{$f})){
					$fields[]=$f;
					$args[':'.$f] = $this->{$f};
				}
			}
			$query = $db->prepare('INSERT INTO channels ( '.implode(', ',$fields).' ) VALUES ( :'.implode(', :',$fields).' )');
			assert($query->execute($args),'Was not able to insert new channel');
	
			$this->id = $db->lastInsertId();
			unset($this->dirty);
		}
		
		$sql = 'INSERT OR IGNORE INTO channels_users (channel_id, user_id) VALUES (:cid, :uid)';
		$query = $db->prepare($sql);
		$query->execute([':cid'=>$this->id,':uid'=>$user->id]);
		foreach ($this->users as $uid) $query->execute([':cid'=>$this->id,':uid'=>$uid]);
		return $this;
	}
	
	function users(){
		global $users;
		if (empty($this->users)){
			$sql = 'SELECT user_id FROM channels_users WHERE channel_id = :cid';
			$db = get_or_create_db();
			$query = $db->prepare($sql);
			assert($query->execute([':cid'=>$this->id]),'Was not able to request channel user list!');
			$rows = $query->fetchAll(PDO::FETCH_ASSOC);
			foreach ($rows as $row) $this->users[] = $row['user_id'];
		}
		if (empty($users)) $users = request('user','json');
		$u = [];
		foreach ($this->users as $uid) $u[$uid] = $users[$uid];
		return $u;
	}
}