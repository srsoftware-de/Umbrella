<?php include '../bootstrap.php';

const MODULE = 'Bookmark';
$title = 'Umbrella Real Time Chat';

function get_or_create_db(){
	$table_filename = 'rtc.db';
	if (!file_exists('db')) assert(mkdir('db'),'Failed to create rtp/db directory!');
	assert(is_writable('db'),'Directory rtc/db not writable!');
	if (!file_exists('db/'.$table_filename)){
		$db = new PDO('sqlite:db/'.$table_filename);

		$tables = [
			'channels'=>Channel::table(),
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

class Channel extends UmbrellaObject{
	static function table(){
		return [
			'users' => ['VARCHAR'=>255,'KEY'=>'PRIMARY'],
			'hash'  => ['VARCHAR'=>255,'NOT NULL'],
			'invite_time' => ['INT','NOT NULL','DEFAULT'=>'0'],
		];
	}

	static function load($options = []){
		global $services,$user;

		$sql = 'SELECT * FROM channels';
		$where = ['users LIKE ?'];
		$args = ['% '.$user->id.' %'];

		if (isset($options['search'])){
			$where[] = 'subject LIKE ?';
			$args[] = '%'.$options['search'].'%';
		}

		$single = false;
		if (isset($options['users'])){
			$users = $options['users'];
			if (!is_array($users)) $users = explode(',',$users);
			$users[] = $user->id;
			$users = array_unique($users);
			sort($users);
			$where[] = 'users = ?';
			$args[] = ' '.implode(' ',$users).' ';
			$single = true;
		}

		if (isset($options['hashes'])){
			$hashes = $options['hashes'];
			if (!is_array($hashes)) {
				$hashes = [$hashes];
				$single = true;
			}
			$qMarks = str_repeat('?,', count($hashes)-1).'?';
			$where[] ='hash IN ('.$qMarks.')';
			$args = array_merge($args, $hashes);
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
			$row['users'] = explode(' ',trim($row['users']));
			$c->patch($row);
			unset($c->dirty);
			if ($single) return $c;
			$channels[$row['hash']] = $c;
		}
		if ($single) return null;
		return $channels;
	}

	function addUsers($users = []){
		$this->patch(['users'=>array_merge($this->users, $users)]);
		return $this->invite($users,'https://meet.jit.si/'.$this->hash);

	}

	function invite($users,$url){
		global $user;

		$users = request('user','json',['ids'=>$users]);
		$recievers = [];
		$names = [];
		foreach ($users as $uid => $u){
			if ($uid == $user->id) continue;
			$recievers[] = $u['email'];
			$names[] = $u['login'];
		}

		$subject = t('? has invited you to a conversation',$user->login);
		$message = t('Go to ◊ to join the conversation. Go to ◊ to see a list of your conversations.',[$url,getUrl('rtc')]);
		send_mail($user->email,$recievers,$subject,$message);
		info('Invitation email was sent to ?.',implode(', ',$names));
		return $this->patch(['invite_time'=>time()])->save();
	}

	function open(){
		global $user;
		$url = 'https://meet.jit.si/'.$this->hash;
		if (empty($this->invite_time) || $this->invite_time < (time()-1800)) $this->invite($this->users,$url);
		redirect($url);
	}

	function save(){
		global $user;
		if (!in_array($user->id,$this->users)) $this->users[]=$user->id;
		asort($this->users);
		$users = ' '.implode(' ',$this->users).' ';
		$db = get_or_create_db();
		$sql = null;
		$args=[];
		if (empty($this->hash)){
			$this->patch(['hash'=>md5($users.'@'.time()),'invite_time'=>0]);
			$sql = 'INSERT OR IGNORE INTO channels (users, hash, invite_time) VALUES ( :users, :hash, :invite_time );';
		} else {
			if (!empty($this->dirty['users'])){
				$query = $db->prepare('DELETE FROM channels WHERE users = :users');
				$query->execute([':users'=>$users]);
			}
			$sql = 'UPDATE channels SET users = :users, invite_time = :invite_time WHERE hash = :hash';
		}
		$args = [':hash'=>$this->hash,':users'=>$users,':invite_time'=>$this->invite_time];
		$query = $db->prepare($sql);
		assert($query->execute($args),'Was not able to store channel in database');

		unset($this->dirty);
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
