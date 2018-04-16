<?php
function get_or_create_db(){
	if (!file_exists('db')) assert(mkdir('db'),'Failed to create model/db directory!');
	assert(is_writable('db'),'Directory model/db not writable!');
	if (!file_exists('db/models.db')){
		$db = new PDO('sqlite:db/models.db');

		$tables = [
			'flows'=>    Flow::fields(),
			'connectors'=> Connector::fields(),
			'models'=>   Model::fields(),
			'processes'=>Process::fields(),
			'terminals'=>Terminal::fields(),
			'models_processes' => Model::process_link(),
			'models_terminals' => Model::terminal_link(),
			'child_processes' => Process::parent_link(),
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
			assert($db->query($sql),'Was not able to create '.$table.' table in companies.db!');
		}
	} else {
		$db = new PDO('sqlite:db/models.db');
	}
	return $db;
}

class Connector{
	/* static functions */
	static function fields(){
		return [
			'id' => ['INTEGER','KEY'=>'PRIMARY'],
			'process_id' => ['INT','NOT NULL'],
			'direction' => ['BOOLEAN'],
			'name' => ['VARCHAR'=>255, 'NOT NULL'],
			'description' => ['TEXT'],
			'angle'=>['INT'],
		];
	}

	static function load($options = []){
		$db = get_or_create_db();
		assert(isset($options['process_id']),'No process id passed to Connector::load()!');

		$sql = 'SELECT * FROM connectors WHERE process_id = ?';
		$args = [$options['process_id']];

		$single = false;
		if (isset($options['ids'])){
			$ids = $options['ids'];
			if (!is_array($ids)) {
				$single = true;
				$ids = [$ids];
			}
			$qMarks = str_repeat('?,', count($ids)-1).'?';
			$sql .= ' WHERE id IN ('.$qMarks.')';
			$args = array_merge($args, $ids);
		}
		$query = $db->prepare($sql);
		assert($query->execute($args),'Was not able to load connectors');
		$rows = $query->fetchAll(PDO::FETCH_ASSOC);
		$connectors = [];

		foreach ($rows as $row){
			$connector = new Connector();
			$connector->patch($row);
			$connector->dirty=[];
			if ($single) return $connector;
			$connectors[$connector->id] = $connector;
		}

		return $connectors;
	}

	/* instance methods */
	function flows(){
		if (!isset($this->flows)) $this->flows = Flow::load(['connector'=>$this->id]);
		return $this->flows;
	}

	function patch($data = array()){
		if (!isset($this->dirty)) $this->dirty = [];
		foreach ($data as $key => $val){
			if ($key === 'id' && isset($this->id)) continue;
			if ($key === 'direction') $val = $val == 'in'||$val==1;
			if (!isset($this->{$key}) || $this->{$key} != $val) $this->dirty[] = $key;
			$this->{$key} = $val;
		}
	}

	public function save(){
		$db = get_or_create_db();
		if (isset($this->id)){
			if (!empty($this->dirty)){
				$sql = 'UPDATE connectors SET';
				$args = [':id'=>$this->id];
				foreach ($this->dirty as $field){
					$sql .= ' '.$field.'=:'.$field.',';
					$args[':'.$field] = $this->{$field};
				}
				$sql = rtrim($sql,',').' WHERE id = :id';
				$query = $db->prepare($sql);
				assert($query->execute($args),'Was no able to update connector in database!');
			}
		} else {
			$known_fields = array_keys(Connector::fields());
			$fields = [];
			$args = [];
			foreach ($known_fields as $f){
				if (isset($this->{$f})){
					$fields[]=$f;
					$args[':'.$f] = $this->{$f};
				}
			}
			$query = $db->prepare('INSERT INTO connectors ( '.implode(', ',$fields).' ) VALUES ( :'.implode(', :',$fields).' )');
			assert($query->execute($args),'Was not able to insert new connector');

			$this->id = $db->lastInsertId();
		}
	}
}

class Flow{
	/** static **/
	const ENDS_IN_CONNECTOR= 0;
	const ENDS_IN_TERMINAL = 1;
	static function fields(){
		return [
			'id' => ['INTEGER','KEY'=>'PRIMARY'],
			'start_type' => ['INT','NOT NULL','DEFAULT 0'],
			'start_id' => ['INT','NOT NULL'],
			'end_type' => ['INT','NOT NULL','DEFAULT 0'],
			'end_id' => ['INT','NOT NULL'],
			'name' => ['VARCHAR'=>255, 'NOT NULL'],
			'description' => ['TEXT'],
			'definition' => ['TEXT'],
		];
	}

	/** instance methods **/
		static function load($options = []){
		$db = get_or_create_db();
		assert(isset($options['connector']),'No connector id passed to Connector::load()!');

		$sql = 'SELECT * FROM flows
				WHERE (start_type = '.Flow::ENDS_IN_CONNECTOR.' AND start_id = ?)
				   OR (end_type = '.Flow::ENDS_IN_CONNECTOR.' AND end_id = ?)';
		$args = [$options['connector'],$options['connector']];

		$single = false;
		if (isset($options['ids'])){
			$ids = $options['ids'];
			if (!is_array($ids)) {
				$single = true;
				$ids = [$ids];
			}
			$qMarks = str_repeat('?,', count($ids)-1).'?';
			$sql .= ' WHERE id IN ('.$qMarks.')';
			$args = array_merge($args, $ids);
		}
		$query = $db->prepare($sql);
		assert($query->execute($args),'Was not able to load flows');
		$rows = $query->fetchAll(PDO::FETCH_ASSOC);
		$flows = [];

		foreach ($rows as $row){
			$flow = new Flow();
			$flow->patch($row);
			$flow->dirty=[];
			if ($single) return $flow;
			$flows[$flow->id] = $flow;
		}
		return $flows;
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
		$db = get_or_create_db();
		if (isset($this->id)){
			if (!empty($this->dirty)){
				$sql = 'UPDATE flows SET';
				$args = [':id'=>$this->id];
				foreach ($this->dirty as $field){
					$sql .= ' '.$field.'=:'.$field.',';
					$args[':'.$field] = $this->{$field};
				}
				$sql = rtrim($sql,',').' WHERE id = :id';
				$query = $db->prepare($sql);
				assert($query->execute($args),'Was no able to update flow in database!');
			}
		} else {
			$known_fields = array_keys(Flow::fields());
			$fields = [];
			$args = [];
			foreach ($known_fields as $f){
				if (isset($this->{$f})){
					$fields[]=$f;
					$args[':'.$f] = $this->{$f};
				}
			}
			$query = $db->prepare('INSERT INTO flows ( '.implode(', ',$fields).' ) VALUES ( :'.implode(', :',$fields).' )');
			assert($query->execute($args),'Was not able to insert new flow');

			$this->id = $db->lastInsertId();
		}
	}
}

class Model{
	/** static functions **/
	static function fields(){
		return [
			'id' => ['INTEGER','KEY'=>'PRIMARY'],
			'project_id' => ['INT','NOT NULL'],
			'name' => ['VARCHAR'=>255, 'NOT NULL'],
			'description' => ['TEXT'],
		];
	}
	
	static function process_link(){
		return [
			'model_id' => ['INT','NOT NULL'],
			'process_id' => ['INT','NOT NULL'],
			'x' => ['INT', 'DEFAULT 30'],
			'y' => ['INT', 'DEFAULT 30'],
			'PRIMARY KEY' => '(model_id, process_id)',
		];
	}
	
	static function terminal_link(){
		return [
		'model_id' => ['INT','NOT NULL'],
		'terminal_id' => ['INT','NOT NULL'],
		'x' => ['INT', 'DEFAULT 30'],
		'y' => ['INT', 'DEFAULT 30'],
		'PRIMARY KEY' => '(model_id, terminal_id)',
		];
	}	

	static function load($options = []){
		global $projects;
		$db = get_or_create_db();

		if (!isset($projects)) $projects = request('project','json');
		$project_ids = array_keys($projects);
		$qMarks = str_repeat('?,', count($project_ids)-1).'?';
		$sql = 'SELECT * FROM models WHERE project_id IN ('.$qMarks.')';
		$args = $project_ids;

		$single = false;
		if (isset($options['ids'])){
			$ids = $options['ids'];
			if (!is_array($ids)) {
				$single = true;
				$ids = [$ids];
			}
			$qMarks = str_repeat('?,', count($ids)-1).'?';
			$sql .= ' AND id IN ('.$qMarks.')';
			$args = array_merge($args, $ids);
		}

		$query = $db->prepare($sql);
		assert($query->execute($args),'Was not able to load models');
		$rows = $query->fetchAll(PDO::FETCH_ASSOC);
		$models = [];

		foreach ($rows as $row){
			$model = new Model();
			$model->patch($row);
			$model->dirty=[];
			$model->project = $projects[$model->project_id];
			if ($single) return $model;
			$models[$model->id] = $model;
		}

		return $models;
	}
	

	/** instance functions **/
	function __construct($project_id = null, $name = null, $description = null){
		$this->project_id = $project_id;
		$this->name = $name;
		$this->description = $description;
	}

	function findConnector($cid){
		foreach ($this->processes() as $proc){
			foreach ($proc->connectors as $conn){
				if ($cid == $conn->id) return $conn;
			}
		}
		return null;
	}
	
	function link($object){
		$db = get_or_create_db();
		if ($object instanceof Process) {
			$sql = 'INSERT INTO models_processes (model_id, process_id) VALUES (:mid, :pid)';
			$args = [':mid' => $this->id, ':pid' => $object->id];
			$query = $db->prepare($sql);
			assert($query->execute($args),'Was not able to assign process to model');
			return;
		}
		if ($object instanceof Terminal) {
			$sql = 'INSERT INTO models_terminals (model_id, terminal_id) VALUES (:mid, :tid)';
			$args = [':mid' => $this->id, ':tid' => $object->id];
			$query = $db->prepare($sql);
			assert($query->execute($args),'Was not able to assign terminal to model');			
			return;
		}
		warn('Model-&gt;link has no handler for '.$object);
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
		$db = get_or_create_db();
		if (isset($this->id)){
			if (!empty($this->dirty)){
				$sql = 'UPDATE models SET';
				$args = [':id'=>$this->id];
				foreach ($this->dirty as $field){
					$sql .= ' '.$field.'=:'.$field.',';
					$args[':'.$field] = $this->{$field};
				}
				$sql = rtrim($sql,',').' WHERE id = :id';
				$query = $db->prepare($sql);
				assert($query->execute($args),'Was no able to update model in database!');
			}
		} else {
			$known_fields = array_keys(Model::fields());
			$fields = [];
			$args = [];
			foreach ($known_fields as $f){
				if (isset($this->{$f})){
					$fields[]=$f;
					$args[':'.$f] = $this->{$f};
				}
			}
			$query = $db->prepare('INSERT INTO models ( '.implode(', ',$fields).' ) VALUES ( :'.implode(', :',$fields).' )');
			assert($query->execute($args),'Was not able to insert new model');

			$this->id = $db->lastInsertId();
		}
	}

	public function terminals($id = null){
		if (!isset($this->terminals)) $this->terminals = Terminal::load(['model_id'=>$this->id]);
		if ($id) return $this->terminals[$id];
		return $this->terminals;
	}

	public function processes($id = null){		
		if (!isset($this->processes)) $this->processes = Process::load(['model_id'=>$this->id]);
		if ($id) return $this->processes[$id];
		return $this->processes;
	}
}

class Process{
	/** static functions **/
	static function fields(){
		return [
			'id' => ['INTEGER','KEY'=>'PRIMARY'],
			'name' => ['VARCHAR'=>255, 'NOT NULL'],
			'description' => 'TEXT',
			'r' => ['INT','DEFAULT 30'],
		];
	}
	
	static function parent_link(){
		return [
			'process_id' => ['INT','NOT NULL'],
			'parent_process' => ['INT','NOT NULL'],
			'x' => ['INT', 'DEFAULT 30'],
			'y' => ['INT', 'DEFAULT 30'],
			'PRIMARY KEY' => '(process_id, parent_process)',
		];
	}

	static function load($options = []){
		$db = get_or_create_db();

		assert(isset($options['model_id']),'No model id passed to Process::load()!');
		
		$where = [];
		$args = [];
		
		$sql = 'SELECT * FROM processes';
		if (isset($options['model_id'])){
			$sql .= ' LEFT JOIN models_processes ON models_processes.process_id = processes.id';
			$where[] = 'model_id = ?';
			$args[] = $options['model_id'];
		}
		
		if (!empty($where)) $sql .= ' WHERE '.implode(' AND ', $where);

/*		$single = false;
		if (isset($options['ids'])){
			$ids = $options['ids'];
			if (!is_array($ids)) {
				$single = true;
				$ids = [$ids];
			}
			$qMarks = str_repeat('?,', count($ids)-1).'?';
			$sql .= ' WHERE id IN ('.$qMarks.')';
			$args = array_merge($args, $ids);
		}*/
		
		$query = $db->prepare($sql);
		assert($query->execute($args),'Was not able to load processes');
		$rows = $query->fetchAll(PDO::FETCH_ASSOC);
		$processes = [];

		foreach ($rows as $row){
			$process = new Process();
			$process->patch($row);
			$process->dirty=[];
			if ($single) return $process;
			$processes[$process->id] = $process;
		}
		return $processes;
	}

	/** instance functions **/

	function connectors($id = null){
		if (!isset($this->connectors)) $this->connectors = Connector::load(['process_id'=>$this->id]);
		if ($id) return $this->connectors[$id];
		return $this->connectors;
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
		$db = get_or_create_db();
		if (isset($this->id)){
			if (!empty($this->dirty)){
				$link_sql = null;
				$link_args = [];
				
				$process_sql = 'UPDATE processes SET';				
				if (isset($this->model_id)) {
					$link_sql = 'UPDATE models_processes SET';
					$link_args = [':pid'=>$this->id,':mid'=>$this->model_id];
				}
				
				$process_args = [':id'=>$this->id];
				
				foreach ($this->dirty as $field){
					if (array_key_exists($field, Process::fields())){
						$process_sql .= ' '.$field.'=:'.$field.',';
						$process_args[':'.$field] = $this->{$field};
					} else {
						$link_sql .= ' '.$field.'=:'.$field.',';
						$link_args[':'.$field] = $this->{$field};
					}
				}
				if (count($process_args)>1){
					$process_sql = rtrim($process_sql,',').' WHERE id = :id';
					//debug(query_insert($process_sql,$process_args),1);
					$query = $db->prepare($process_sql);
					assert($query->execute($process_args),'Was no able to update process in database!');
				}
				
				if ($link_sql && count($link_args)>2){
					$link_sql = rtrim($link_sql,',').' WHERE process_id = :pid AND model_id = :mid';
					//debug(query_insert($link_sql.' ',$link_args),1);
					$query = $db->prepare($link_sql);
					assert($query->execute($link_args),'Was no able to update process link in database!');						
				}
			}
		} else {
			$known_fields = array_keys(Process::fields());
			$fields = [];
			$args = [];
			foreach ($known_fields as $f){
				if (isset($this->{$f})){
					$fields[]=$f;
					$args[':'.$f] = $this->{$f};
				}
			}
			$query = $db->prepare('INSERT INTO processes ( '.implode(', ',$fields).' ) VALUES ( :'.implode(', :',$fields).' )');
			assert($query->execute($args),'Was not able to insert new process');

			$this->id = $db->lastInsertId();
		}
	}

}

class Terminal{
	const TERMINAL = 0;
	const DATABASE = 1;

	/** static functions **/
	static function fields(){
		return [
			'id' => ['INTEGER','KEY'=>'PRIMARY'],
			'type' => 'INT',
			'name' => ['VARCHAR'=>255, 'NOT NULL'],
			'description' => 'TEXT',
			'w' => ['INT','DEFAULT 50'],
		];
	}

	static function load($options = []){
		$db = get_or_create_db();

		assert(isset($options['model_id']),'No model id passed to Process::load()!');
		
		$where = [];
		$args = [];
		
		$sql = 'SELECT * FROM terminals';
		if (isset($options['model_id'])){
			$sql .= ' LEFT JOIN models_terminals ON models_terminals.terminal_id = terminals.id';
			$where[] = 'model_id = ?';
			$args[] = $options['model_id'];
		}
		
		if (!empty($where)) $sql .= ' WHERE '.implode(' AND ', $where);

/*		$single = false;
		if (isset($options['ids'])){
			$ids = $options['ids'];
			if (!is_array($ids)) {
				$single = true;
				$ids = [$ids];
			}
			$qMarks = str_repeat('?,', count($ids)-1).'?';
			$sql .= ' WHERE id IN ('.$qMarks.')';
			$args = array_merge($args, $ids);
		}*/
		//debug(query_insert($sql.' ',$args),1);
		$query = $db->prepare($sql);
		assert($query->execute($args),'Was not able to load terminals');
		$rows = $query->fetchAll(PDO::FETCH_ASSOC);
		$terminals = [];

		foreach ($rows as $row){
			$terminal = new Terminal();
			$terminal->patch($row);
			$terminal->dirty=[];
			if ($single) return $terminal;
			$terminals[$terminal->id] = $terminal;
		}
		return $terminals;
	}

	/** instance functions **/
	function patch($data = array()){
		if (!isset($this->dirty)) $this->dirty = [];
		foreach ($data as $key => $val){
			if ($key === 'id' && isset($this->id)) continue;
			if (!isset($this->{$key}) || $this->{$key} != $val) $this->dirty[] = $key;
			$this->{$key} = $val;
		}
	}

	public function save(){
		$db = get_or_create_db();
		if (isset($this->id)){
			if (!empty($this->dirty)){
				$link_sql = null;
				$link_args = [];
				
				$process_sql = 'UPDATE terminals SET';				
				if (isset($this->model_id)) {
					$link_sql = 'UPDATE models_terminals SET';
					$link_args = [':tid'=>$this->id,':mid'=>$this->model_id];
				}
				
				$process_args = [':id'=>$this->id];
				
				foreach ($this->dirty as $field){
					if (array_key_exists($field, Terminal::fields())){
						$process_sql .= ' '.$field.'=:'.$field.',';
						$process_args[':'.$field] = $this->{$field};
					} else {
						$link_sql .= ' '.$field.'=:'.$field.',';
						$link_args[':'.$field] = $this->{$field};
					}
				}
				if (count($process_args)>1){
					$process_sql = rtrim($process_sql,',').' WHERE id = :id';
					//debug(query_insert($process_sql,$process_args),1);
					$query = $db->prepare($process_sql);
					assert($query->execute($process_args),'Was no able to update terminal in database!');
				}
				
				if ($link_sql && count($link_args)>2){
					$link_sql = rtrim($link_sql,',').' WHERE terminal_id = :tid AND model_id = :mid';
					//debug(query_insert($link_sql.' ',$link_args),1);
					$query = $db->prepare($link_sql);
					assert($query->execute($link_args),'Was no able to update terminal link in database!');						
				}
			}
		} else {
			$known_fields = array_keys(Terminal::fields());
			$fields = [];
			$args = [];
			foreach ($known_fields as $f){
				if (isset($this->{$f})){
					$fields[]=$f;
					$args[':'.$f] = $this->{$f};
				}
			}
			$query = $db->prepare('INSERT INTO terminals ( '.implode(', ',$fields).' ) VALUES ( :'.implode(', :',$fields).' )');
			assert($query->execute($args),'Was not able to insert new terminal');
			$this->id = $db->lastInsertId();
		}
	}
}
