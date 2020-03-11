<?php include '../bootstrap.php';

const MODULE = 'Poll';
const DB_VERSION = 1;
$title = t('Umbrella Poll Management');

function db_version(){
	$db = get_or_create_db();
	$query = $db->prepare('SELECT value FROM settings WHERE key = "db_version"');
	if (!$query->execute()) throw new Exception(_('Failed to query db_version!'));
	$rows = $query->fetchAll(PDO::FETCH_COLUMN);
	if (empty($rows)) return null;
	return reset($rows);
}

function get_or_create_db(){
	if (!file_exists('db') && !mkdir('db')) throw new Exception('Failed to create poll/db directory!');
	if (!is_writable('db')) throw new Exception('Directory poll/db not writable!');
	if (!file_exists('db/poll.db')){
		$db = new PDO('sqlite:db/poll.db');

		$tables = [
				'polls'      => Poll::table(),
				'options'    => Option::table(),
				'weights'    => Poll::weights_table(),
				'selections' => Poll::selections_table(),
				'shares'     => Poll::shares_table(),
		];

		foreach ($tables as $table => $fields){
			$sql = 'CREATE TABLE '.$table.' ( ';
			foreach ($fields as $field => $props) $sql .= field_description($field, $props);
			$sql = str_replace([' ,',', )'],[',',')'],$sql.')');
			if (!$db->query($sql)) throw new Exception('Was not able to create '.$table.' table in poll.db!');
		}
	} else {
		$db = new PDO('sqlite:db/poll.db');
	}
	return $db;
}

class Option extends UmbrellaObject{
	const DISABLED = 2;
	const ENABLED = 1;
	const HIDDEN = 0;

	static function table() {
		return [
				'id' => ['INT','NOT NULL'],
				'poll_id'=>['VARCHAR'=>255,'NOT NULL','REFERENCES polls(id)'],
				'name'=>['VARCHAR'=>255,'NOT NULL'],
				'description'=>['TEXT'],
				'statsus'=>['INT','DEFAULT 0'],
				'PRIMARY KEY'=>['id','poll_id']
		];
	}

	static function load($options){
		$sql = 'SELECT * FROM options';

		$where = [];
		$args = [];

		if (!empty($options['poll_id'])){
			$where = ['poll_id = ?'];
			$args[] = $options['poll_id'];
		}

		$single = false;

		if (!empty($options['ids'])){
			$ids = $options['ids'];
			if (!is_array($ids)) {
				$single = true;
				$ids = [$ids];
			}
			$qMarks = str_repeat('?,', count($ids)-1).'?';
			$where[] = 'id IN ('.$qMarks.')';
			$args = array_merge($args, $ids);
		}

		if (!empty($where)) $sql .= ' WHERE ('.implode(') AND (', $where).')';
		//debug(['options'=>$options,'query'=>query_insert($sql, $args)]);
		$db = get_or_create_db();
		$query = $db->prepare($sql);
		if (!$query->execute($args)) throw new Exception('Was not able to read polls!');

		$rows = $query->fetchAll($single?PDO::FETCH_ASSOC:INDEX_FETCH);
		//debug(['options'=>$options,'query'=>query_insert($sql, $args),'rows'=>$rows]);
		$polls = [];

		foreach ($rows as $id => $row){
			$poll = new Option();
			$poll->patch($row);
			$poll->patch(['id'=>$id]);
			unset($poll->dirty);

			if ($single) return $poll;
			$polls[$id] = $poll;
		}
		if ($single) return null;
		return $polls;
	}

	function save() {
		$poll = Poll::load(['ids'=>$this->poll_id]);

		foreach ($this as $field => $val) {
			if (is_array($val)) continue;
			$this->{$field} = trim($val);
		}

		debug(['poll'=>$poll,'option'=>$this]);

		if (!empty($this->id)) return $this->update();


		$fields = Option::table();

		$keys = [ 'status' ];
		$args = [ ':status'=>Option::ENABLED ];
		foreach ($this as $field => $val){
			if (array_key_exists($field, $fields)){
				$keys[] = $field;
				$args[':'.$field] = $val;
			}
		}

		$keys[] = 'id';
		$args[':id'] = 1+count($poll->options());

		$sql = 'INSERT INTO options ('.implode(', ', $keys).') VALUES (:'.implode(', :',$keys).' )';
		$query = get_or_create_db()->prepare($sql);
		if (!$query->execute($args)) throw new Exception('Was not able to add option to poll!');
		unset($this->dirty);

		return $this;
	}

	function update(){
		$db = get_or_create_db();
		$sql = 'UPDATE options SET name = :name, description = :desc WHERE poll_id = :pid AND id = :id';
		$args = [':name'=>$this->name,':desc'=>$this->description,':pid'=>$this->poll_id,':id'=>$this->id];
		$query = $db->prepare($sql);

		if (!$query->execute($args)) throw new Exception('Was not able to update poll option!');
		unset($this->dirty);
		return $this;
	}
}

class Poll extends UmbrellaObjectWithId{
	const PARTICIPATE = 0;
	const EVALUATE = 1;
	const EDIT = 2;

	static function table(){
		return [
				'id' => ['VARCHAR'=>255,'NOT NULL','PRIMARY KEY'],
				'user_id' => ['INT','NOT NULL','REFERENCES user(id)'],
				'name' => ['VARCHAR'=>255,'NOT NULL'],
				'description' => ['TEXT']
		];
	}

	static function shares_table(){
		return [
			'poll_id'=>['VARCHAR'=>255,'NOT NULL','REFERENCES polls(id)'],
			'user_id' => ['INT','NOT NULL','REFERENCES user(id)'],
			'permission' => ['INT'],
			'PRIMARY KEY'=>['poll_id','user_id'],
		];
	}

	static function weights_table(){
		return [
				'weight'=>['INT','NOT NULL'],
				'poll_id'=>['VARCHAR'=>255,'NOT NULL','REFERENCES polls(id)'],
				'description'=>['TEXT'],
				'PRIMARY KEY'=>['poll_id','weight']
		];
	}

	static function selections_table() {
		return [
				'option_id'=>['INT','NOT NULL','REFERENCES options(id)'],
				'poll_id'=>['VARCHAR'=>255,'NOT NULL','REFERENCES polls(id)'],
				'user'=>['VARCHAR'=>255,'NOT NULL'],
				'weight'=>['INT','NOT NULL','REFERENCES weights(weight)'],
				'PRIMARY KEY'=>['poll_id','user','option_id']
		];
	}
	/**** end of table functions ******/
	static function load($options){
		$sql = 'SELECT * FROM polls';

		$where = [];
		$args = [];

		$single = false;

		if (!empty($options['key'])){
			$key = '%'.$options['key'].'%';
			$where[] = 'name LIKE ? OR description LIKE ? OR id IN (SELECT poll_id FROM options WHERE name LIKE ? OR description LIKE ?)';
			$args[] = $key;
			$args[] = $key;
			$args[] = $key;
			$args[] = $key;
		}

		if (!empty($options['ids'])){
			$ids = $options['ids'];
			if (!is_array($ids)) {
				$single = true;
				$ids = [$ids];
			}
			$qMarks = str_repeat('?,', count($ids)-1).'?';
			$where[] = 'id IN ('.$qMarks.')';
			$args = array_merge($args, $ids);
		}

		if (!empty($where)) $sql .= ' WHERE ('.implode(') AND (', $where).')';
		//debug(['options'=>$options,'query'=>query_insert($sql, $args)]);
		$db = get_or_create_db();
		$query = $db->prepare($sql);
		if (!$query->execute($args)) throw new Exception('Was not able to read polls!');

		$rows = $query->fetchAll(PDO::FETCH_ASSOC);
		//debug(['options'=>$options,'query'=>query_insert($query, $args),'rows'=>$rows]);
		$polls = [];

		foreach ($rows as $row){
			$poll = new Poll();
			$poll->patch($row);
			unset($poll->dirty);

			if ($single) return $poll;
			$id = $row['id'];
			$polls[$id] = $poll;
		}
		if ($single) return null;
		return $polls;
	}
	/**** end of static functions ******/
	function add_weight($data){
		$fields = Poll::weights_table();

		$keys = [];
		$args = [];
		foreach ($data as $field => $val){
			if (array_key_exists($field, $fields)){
				$keys[] = $field;
				$args[':'.$field] = $val;
			}
		}

		$keys[] = 'poll_id';

		$args[':poll_id']=$this->id;
		$sql = 'INSERT INTO weights ('.implode(', ', $keys).') VALUES (:'.implode(', :',$keys).' )';
		if (!get_or_create_db()->prepare($sql)->execute($args)) throw new Exception('Was not able to add weight to poll!');
	}

	function set_option_status($id,$status = Option::ENABLED){
		$sql = 'UPDATE options SET status = :status WHERE id = :id AND poll_id = :pid';
		$args = [':id'=>$id,':pid'=>$this->id,':status'=>$status];
		if (!get_or_create_db()->prepare($sql)->execute($args)) throw new Exception('Was not able alter option status!');
	}

	function get_selections($user){
		$sql = 'SELECT * FROM selections WHERE poll_id = :poll AND user = :user';
		$query = get_or_create_db()->prepare($sql);
		$args = [':poll'=>$this->id, ':user'=>$user];
		if (!$query->execute($args)) throw new Exception('Was not able to read selections!');
		return $query->fetchAll(INDEX_FETCH);
	}

	function options(){
		if (empty($this->options)) $this->options = Option::load(['poll_id'=>$this->id]);
		return $this->options;
	}

	function remove_option($id){
		$db = get_or_create_db();
		$db->beginTransaction();

		$args = [':id'=>$id,':pid'=>$this->id];

		$sql = 'DELETE FROM options WHERE poll_id = :pid AND id = :id';
		$query = $db->prepare($sql);
		$query->execute($args);

		$sql = 'UPDATE options SET id = id -1 WHERE poll_id = :pid AND id > :id';
		$query = $db->prepare($sql);
		$query->execute($args);

		$sql = 'DELETE FROM selections WHERE option_id = :id AND poll_id = :pid';
		$query = $db->prepare($sql);
		$query->execute($args);

		$sql = 'UPDATE selections SET option_id = option_id -1 WHERE option_id > :id AND poll_id = :pid';
		$query = $db->prepare($sql);
		$query->execute($args);

		$db->commit();
	}

	function remove_votes_of($user){
		$sql = 'DELETE FROM selections WHERE poll_id = :pid AND user = :user';
		$args = [':pid'=>$this->id,':user'=>$user];
		$db = get_or_create_db();
		$query = $db->prepare($sql);
		if (!$query->execute($args)) throw new Exception('Was not able to remove votes of '.$user);
	}

	function remove_weight($w){
		$sql = 'DELETE FROM weights WHERE weight = :w AND poll_id = :pid';
		$args = [':w'=>$w,':pid'=>$this->id];
		if (!get_or_create_db()->prepare($sql)->execute($args)) throw new Exception('Was not able to remove weight from poll!');
	}

	function save() {
		global $user;
		$this->patch(['user_id'=>$user->id]);
		foreach ($this as $field => $val) {
			if (is_array($val)) continue;
			$this->{$field} = trim($val);
		}
		if (!empty($this->id)) return $this->update();

		$this->id = sha1($this->name.time());

		$sql = 'INSERT INTO polls (id, user_id, name, description) VALUES (:id, :uid, :name, :desc)';
		$args = [':id'=>$this->id, ':uid'=>$this->user_id, ':name'=>$this->name, ':desc'=>$this->description];

		$query = get_or_create_db()->prepare($sql);
		if (!$query->execute($args)) throw new Exception('Was not able to put new poll into database!');

		unset($this->dirty);
		return $this;
	}

	function save_selection($data){
		$sql = 'REPLACE INTO selections (poll_id, user, option_id, weight) VALUES (:p, :u, :o, :w )';
		$query = get_or_create_db()->prepare($sql);
		foreach ($data['option'] as $oid => $weight){
			$args = [':p'=>$this->id, ':u'=>$data['name'],':o'=>$oid,':w'=>$weight];
			if (!$query->execute($args)) throw new Exception('Was not able to store selection!');
		}
	}

	function selections(){
		$sql = 'SELECT * FROM selections WHERE poll_id = :id';
		$query = get_or_create_db()->prepare($sql);
		$args = [':id'=>$this->id];
		if (!$query->execute($args)) throw new Exception('Was not able to read selections of poll!');
		$rows = $query->fetchAll(PDO::FETCH_ASSOC);
		$selections = [];
		foreach ($rows as $row){
			$user = $row['user'];
			$option_id = $row['option_id'];
			$weight = $row['weight'];
			if (empty($selections[$user])) $selections[$user] = [];
			$selections[$user][$option_id] = $weight;
		}
		return $selections;
	}

	function share($permissions){
		if (!is_array($permissions)) throw new Error('Invalid parameter passed to Poll->share('.print_r($permissions,true).')');
		$sql = 'REPLACE INTO shares (poll_id, user_id, permission) VALUES (:pid, :uid, :perm )';
		$args = [':pid'=>$this->id];
		$query = get_or_create_db()->prepare($sql);
		foreach ($permissions as $user_id => $permission){
			$args[':uid'] = $user_id;
			$args[':perm']= $permission;
			if (!$query->execute($args)) throw new Exception('Was not able to update shares of '+$this->name);
		}
	}

	function update(){
		$sql = 'UPDATE polls SET name = :name, description = :desc WHERE id = :id';
		$args = [':name'=>$this->name,':desc'=>$this->description,':id'=>$this->id];
		$query = get_or_create_db()->prepare($sql);

		if (!$query->execute($args)) throw new Exception('Was not able to update poll!');
		unset($this->dirty);
		return $this;
	}

	function users($id = null){
		if (empty($this->users)){
			$sql = 'SELECT * FROM shares WHERE poll_id = :pid';
			$args = [':pid'=>$this->id];
			$query = get_or_create_db()->prepare($sql);
			if (!$query->execute($args)) throw new Exception('Was not able to read shares of poll!');
			$rows = $query->fetchAll(PDO::FETCH_ASSOC);
			$this->users = [];
			foreach ($rows as $row) {
				$user_id = $row['user_id'];
				$this->users[$user_id] = $row['permission'];
			}
			$this->users[$this->user_id] = Poll::EDIT; // owner is always allowed to edit
		}
		if (!empty($id)) {
			if (array_key_exists($id, $this->users)) return $this->users[$id];
			return Poll::PARTICIPATE;
		}
		return $this->users;
	}

	function weights(){
		if (empty($this->weights)){
			$sql = 'SELECT * FROM weights WHERE poll_id = :pid';
			$args = [':pid'=>$this->id];
			$query = get_or_create_db()->prepare($sql);
			if (!$query->execute($args)) throw new Exception('Was not able to read poll options from database!');
			$this->weights = $query->fetchAll(INDEX_FETCH);
		}
		return $this->weights;
	}
}

class Settings {
	static function table(){
		return [
				'key'	=> ['VARCHAR'=>255,'KEY'=>'PRIMARY'],
				'value'	=> ['VARCHAR'=>255,'NOT NULL'],
		];
	}
}