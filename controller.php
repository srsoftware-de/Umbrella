<?php
function get_or_create_db(){
	if (!file_exists('db')) assert(mkdir('db'),'Failed to create rtc/db directory!');
	assert(is_writable('db'),'Directory rtc/db not writable!');
	if (!file_exists('db/rtc.db')){
		$db = new PDO('sqlite:db/rtc.db');

		$tables = [
			'messages'=>Message::table()
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
		$db = new PDO('sqlite:db/rtc.db');
	}
	return $db;
}

class Message{
	static function clean(){
		$db = get_or_create_db();
		$hour = 60*60*1000; // miliseconds
		$db->exec('DELETE FROM messages WHERE time < '.(static::time()-$hour));
	}
	
	static function load($options = []){
		$db = get_or_create_db();
		
		$args = [];
		$where = [];
		$limit = '';
		$single = false;
		$sql = 'SELECT * FROM messages';

		if (isset($options['from'])) {
			$where['time > :from'] 	= [':from' => $options['from']];
			$limit = ' LIMIT 1';
			$single = true;
		}
		if (!isset($options['echo'])){ // if echo is set: return messages to sender, too
			$where['ip != :ip']		= [':ip' => $_SERVER['HTTP_X_REAL_IP']];				
		}
		
		if (!empty($where)){
			$sql .= ' WHERE '.implode(' AND ', array_keys($where));
			foreach ($where as $cond => $where_args) $args = array_merge($args,$where_args);
		}		
		
		$query = $db->prepare($sql.$limit);
		//debug(query_insert($query,$args));
		$query->execute($args);
		$messages = [];
		foreach ($query->fetchAll(INDEX_FETCH) as $row){
			$message = new Message();
			$message->patch($row);
			unset($message->dirty);
			if ($single) return $message;
			$messages[$message->id] = $message;
		}
		if ($single) return null; // result set is empty, loop will not be entered
		return $messages;
	}
	
	static function process(){
		$subject = param('subject');
		$text = param('text');
		$ip = $_SERVER['HTTP_X_REAL_IP'];
		$message = new Message($ip,$subject,$text);
		$message->save();
	}
	
	static function table(){
		return [
			'time'		=> ['INT','KEY'=>'PRIMARY'],
			'ip'		=> ['VARCHAR'=>255,'NOT NULL'],
			'text'		=> 'TEXT',
		];
	}
	
	static function time(){
		return round(microtime(true)*1000);
	}
	/*** END OF STATIC FUNCTIONS **/
	
	function __construct($text, $time = null, $ip = null){
		if ($ip === null) $ip = $_SERVER['HTTP_X_REAL_IP'];
		if ($time === null) $time = static::time();
		$this->patch(['ip'=>$ip,'text'=>$text,'time'=>$time]);
	}
	
	function patch($data = array()){
		if (!isset($this->dirty)) $this->dirty = [];
		foreach ($data as $key => $val){
			if (!isset($this->{$key}) || $this->{$key} != $val) $this->dirty[] = $key;
			$this->{$key} = $val;
		}
	}
	
	public function save(){
		global $user;
		$db = get_or_create_db();
		$known_fields = array_keys(Message::table());
		$fields = [];
		$args = [];
		foreach ($known_fields as $f){
			if (isset($this->{$f})){
				$fields[]=$f;
				$args[':'.$f] = $this->{$f};
			}
		}
		$sql = 'INSERT INTO messages ( '.implode(', ',$fields).' ) VALUES ( :'.implode(', :',$fields).' )';
		$query = $db->prepare($sql);
		assert($query->execute($args),'Was not able to insert new message');
		$this->id = $db->lastInsertId();
		$this->dirty = [];
		
		static::clean();
	}
}