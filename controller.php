<?php include '../bootstrap.php';

const MODULE = 'Poll';
$title = t('Umbrella Poll Management');

function get_or_create_db(){
	if (!file_exists('db')) assert(mkdir('db'),'Failed to create poll/db directory!');
	assert(is_writable('db'),'Directory poll/db not writable!');
	if (!file_exists('db/poll.db')){
		$db = new PDO('sqlite:db/poll.db');

		$tables = [
				'polls'      => Poll::table(),
				'options'    => Poll::options_table(),
				'weights'    => Poll::weights_table(),
				'selections' => Poll::selections_table(),
		];

		foreach ($tables as $table => $fields){
			$sql = 'CREATE TABLE '.$table.' ( ';
			foreach ($fields as $field => $props) $sql .= field_description($field, $props);
			$sql = str_replace([' ,',', )'],[',',')'],$sql.')');
			assert($db->query($sql),'Was not able to create '.$table.' table in poll.db!');
		}
	} else {
		$db = new PDO('sqlite:db/poll.db');
	}
	return $db;
}

function markdown($text){
	if (file_exists('../lib/parsedown/Parsedown.php')){
		include_once '../lib/parsedown/Parsedown.php';
		return Parsedown::instance()->parse($text);
	} else {
		return str_replace("\n", "<br/>", htmlentities($text));
	}
}


class Poll extends UmbrellaObjectWithId{
	static function table(){
		return [
				'id' => ['VARCHAR'=>255,'NOT NULL','PRIMARY KEY'],
				'user_id' => ['INT','NOT NULL','REFERENCES user(id)'],
				'name' => ['VARCHAR'=>255,'NOT NULL'],
				'description' => ['TEXT']
		];
	}

	static function options_table() {
		return [
				'id' => ['INT','NOT NULL'],
				'poll_id'=>['VARCHAR'=>255,'NOT NULL','REFERENCES polls(id)'],
				'name'=>['VARCHAR'=>255,'NOT NULL'],
				'description'=>['TEXT'],
				'PRIMARY KEY'=>['id','poll_id']
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
				'poll_id'=>['VARCHAR'=>255,'NOT NULL','REFERENCES polls(id)'],
				'options_id'=>['INT','NOT NULL','REFERENCES options(id)'],
				'user'=>['VARCHAR'=>255,'NOT NULL'],
				'weigth'=>['INT','NOT NULL','REFERENCES weights(weight)']
		];
	}
	/**** end of table functions ******/
	static function load($options){
		global $user;
		$sql = 'SELECT * FROM polls';
		$where = ['user_id = ?'];
		$args = [$user->id];
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
			$poll = new Poll();
			$poll->patch($row);
			unset($poll->dirty);

			if ($single) return $poll;
			$polls[$id] = $poll;
		}
		if ($single) return null;
		return $polls;
	}
	/**** end of static functions ******/
	function add_option($data){
		$fields = Poll::options_table();

		$keys = [];
		$args = [];
		foreach ($data as $field => $val){
			if (array_key_exists($field, $fields)){
				$keys[] = $field;
				$args[':'.$field] = $val;
			}
		}

		$keys[] = 'id';
		$keys[] = 'poll_id';

		$args[':id'] = 1+count($this->options());
		$args[':poll_id']=$this->id;
		$sql = 'INSERT INTO options ('.implode(', ', $keys).') VALUES (:'.implode(', :',$keys).' )';
		if (!get_or_create_db()->prepare($sql)->execute($args)) throw new Exception('Was not able to add option to poll!');
	}

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

	function options(){
		if (empty($this->options)){
			$sql = 'SELECT * FROM options WHERE poll_id = :pid';
			$args = [':pid'=>$this->id];
			$query = get_or_create_db()->prepare($sql);
			if (!$query->execute($args)) throw new Exception('Was not able to read poll options from database!');
			$this->options = $query->fetchAll(INDEX_FETCH);
		}
		return $this->options;
	}

	function remove_option($id){
		$sql = 'DELETE FROM options WHERE id = :id AND poll_id = :pid';
		$args = [':id'=>$id,':pid'=>$this->id];
		if (!get_or_create_db()->prepare($sql)->execute($args)) throw new Exception('Was not able to remove option from poll!');
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
