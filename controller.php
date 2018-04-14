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

	/* instance methods */
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

	function patch($data = array()){
		if (!isset($this->dirty)) $this->dirty = [];
		foreach ($data as $key => $val){
			if ($key === 'id' && isset($this->id)) continue;
			if ($key === 'direction') $val = $val == 'in'||$val==1;
			if (isset($this->{$key}) && $this->{$key} != $val) $this->dirty[] = $key;
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

	function patch($data = array()){
		if (!isset($this->dirty)) $this->dirty = [];
		foreach ($data as $key => $val){
			if ($key === 'id' && isset($this->id)) continue;
			if (isset($this->{$key}) && $this->{$key} != $val) $this->dirty[] = $key;
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

	function patch($data = array()){
		if (!isset($this->dirty)) $this->dirty = [];
		foreach ($data as $key => $val){
			if ($key === 'id' && isset($this->id)) continue;
			if (isset($this->{$key}) && $this->{$key} != $val) $this->dirty[] = $key;
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
			'model_id' => ['INT','NOT NULL'],
			'parent_id' => 'INT',
			'name' => ['VARCHAR'=>255, 'NOT NULL'],
			'description' => 'TEXT',
			'r' => 'INT',
			'x' => 'INT',
			'y' => 'INT',
		];
	}

	static function load($options = []){

		$db = get_or_create_db();

		assert(isset($options['model_id']),'No model id passed to Process::load()!');

		$sql = 'SELECT * FROM processes WHERE model_id = ?';
		$args = [$options['model_id']];

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
		assert($query->execute($args),'Was not able to load processes');
		$rows = $query->fetchAll(PDO::FETCH_ASSOC);
		$processes = [];

		foreach ($rows as $row){
			$process = new Process($row['name'],$row['model_id']);
			$process->patch($row);
			$process->dirty=[];
			if ($single) return $process;
			$processes[$process->id] = $process;
		}

		return $processes;
	}

	/** instance functions **/
	function __construct($name,$model_id,$description = null,$parent_id = null){
		$this->model_id = $model_id;
		$this->name = $name;
		$this->description = $description;
		$this->parent_id = $parent_id;
	}

	function connectors($id = null){
		if (!isset($this->connectors)) $this->connectors = Connector::load(['process_id'=>$this->id]);
		if ($id) return $this->connectors[$id];
		return $this->connectors;
	}

	function patch($data = array()){
		if (!isset($this->dirty)) $this->dirty = [];
		foreach ($data as $key => $val){
			if ($key === 'id' && isset($this->id)) continue;
			if (isset($this->{$key}) && $this->{$key} != $val) $this->dirty[] = $key;
			$this->{$key} = $val;
		}
	}

	public function save(){
		$db = get_or_create_db();
		if (isset($this->id)){
			if (!empty($this->dirty)){
				$sql = 'UPDATE processes SET';
				$args = [':id'=>$this->id];
				foreach ($this->dirty as $field){
					$sql .= ' '.$field.'=:'.$field.',';
					$args[':'.$field] = $this->{$field};
				}
				$sql = rtrim($sql,',').' WHERE id = :id';
				$query = $db->prepare($sql);
				assert($query->execute($args),'Was no able to update process in database!');
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
			'model_id' => ['INT','NOT NULL'],
			'type' => 'INT',
			'name' => ['VARCHAR'=>255, 'NOT NULL'],
			'description' => 'TEXT',
			'w' => 'INT',
			'x' => 'INT',
			'y' => 'INT',
		];
	}

	static function load($options = []){

		$db = get_or_create_db();

		assert(isset($options['model_id']),'No model id passed to Terminal::load()!');

		$sql = 'SELECT * FROM terminals WHERE model_id = ?';
		$args = [$options['model_id']];

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
		assert($query->execute($args),'Was not able to load terminals');
		$rows = $query->fetchAll(PDO::FETCH_ASSOC);
		$terminals = [];

		foreach ($rows as $row){
			$terminal = new Terminal($row['name'],$row['model_id']);
			$terminal->patch($row);
			$terminal->dirty=[];
			if ($single) return $terminal;
			$terminals[$terminal->id] = $terminal;
		}

		return $terminals;
	}

	/** instance functions **/
	function __construct($name,$model_id,$description = null,$type = null){
		$this->model_id = $model_id;
		$this->name = $name;
		$this->description = $description;
		$this->type = $type;
	}

	function patch($data = array()){
		if (!isset($this->dirty)) $this->dirty = [];
		foreach ($data as $key => $val){
			if ($key === 'id' && isset($this->id)) continue;
			if (isset($this->{$key}) && $this->{$key} != $val) $this->dirty[] = $key;
			$this->{$key} = $val;
		}
	}

	public function save(){
		$db = get_or_create_db();
		if (isset($this->id)){
			if (!empty($this->dirty)){
				$sql = 'UPDATE terminals SET';
				$args = [':id'=>$this->id];
				foreach ($this->dirty as $field){
					$sql .= ' '.$field.'=:'.$field.',';
					$args[':'.$field] = $this->{$field};
				}
				$sql = rtrim($sql,',').' WHERE id = :id';
				$query = $db->prepare($sql);
				assert($query->execute($args),'Was no able to update terminal in database!');
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
