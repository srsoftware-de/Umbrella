<?php

const RAD = 0.01745329;

function get_or_create_db(){
	if (!file_exists('db')) assert(mkdir('db'),'Failed to create model/db directory!');
	assert(is_writable('db'),'Directory model/db not writable!');
	if (!file_exists('db/models.db')){
		$db = new PDO('sqlite:db/models.db');

		$tables = [
			'flows'=>    FlowBase::fields(),
			'flow_instances'=>    Flow::fields(),
			'connectors'=> ConnectorBase::fields(),
			'connector_instances'=> Connector::fields(),
			'models'=>   Model::fields(),
			'processes'=>ProcessBase::fields(),
			'process_instances'=>Process::fields(),
			'terminals'=>TerminalBase::fields(),
			'terminal_instances'=>Terminal::fields(),
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
								assert($prop_v === 'PRIMARY','Non-primary keys not implemented in model/controller.php!');
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

function arrow($x1,$y1,$x2,$y2,$text = null,$link = null){
if ($link){ ?><a xlink:href="<?= $link ?>"><?php } ?>
<g class="arrow">
<line x1="<?= $x1 ?>" y1="<?= $y1 ?>" x2="<?= $x2 ?>" y2="<?= $y2 ?>" />
<?php $dx = $x2 - $x1; $dy = $y2 - $y1;	$alpha = ($dy == 0) ? 0 : atan($dx/$dy); $x1 = $x2 - 25*sin($alpha+0.2); $y1 = $y2 - 25*cos($alpha+0.2); ?>


<line x1="<?= $x1 ?>" y1="<?= $y1 ?>" x2="<?= $x2 ?>" y2="<?= $y2 ?>" />
<?php $x1 = $x2 - 25*sin($alpha-0.2); $y1 = $y2 - 25*cos($alpha-0.2); ?>
<line x1="<?= $x1 ?>" y1="<?= $y1 ?>" x2="<?= $x2 ?>" y2="<?= $y2 ?>" />
<circle cx="<?= $x2-$dx/2 ?>" cy="<?= $y2-$dy/2 ?>" r="15" /><text x="<?= $x2-$dx/2 ?>" y="<?= $y2-$dy/2 ?>"><?= $text ?></text>
</g>
<?php if ($link){ ?></a><?php } 
}

class BaseClass{
	function patch($data = array()){
		if (!isset($this->dirty)) $this->dirty = [];
		foreach ($data as $key => $val){
			if ($key === 'id' && isset($this->id)) continue;
			if (!isset($this->{$key}) || $this->{$key} != $val) $this->dirty[] = $key;
			$this->{$key} = $val;
		}
	}
}

class ConnectorBase extends BaseClass{
	/* static functions */
	static function fields(){
		return [
			'id' => ['VARCHAR'=>255, 'NOT NULL'],
			'model_id' => ['INT','NOT NULL'],
			'process_id' => ['INT','NOT NULL'],
			'direction' => ['BOOLEAN'],
			'PRIMARY KEY'=>'(id, model_id)',
		];
	}
	
	static function load($options = []){
		$db = get_or_create_db();
		assert(isset($options['model_id']),'No model id passed to ConnectorBase::load()!');
		
		$sql = 'SELECT * FROM connectors';
		$where = ['model_id = ?'];
		$args = [$options['model_id']];
	
		$single = false;
		if (isset($options['ids'])){
			$ids = $options['ids'];
			if (!is_array($ids)) {
				$single = true;
				$ids = [$ids];
			}
			$qMarks = str_repeat('?,', count($ids)-1).'?';
			$where[] = 'id IN ('.$qMarks.')';
			$args = array_merge($args, $ids);
		}
		
		if (isset($options['process_id'])){
			$where[] = 'process_id = ?';
			$args[] = $options['process_id'];
		}
		
		$query = $db->prepare($sql.' WHERE '.implode(' AND ',$where));

		assert($query->execute($args),'Was not able to load connectors');
		$rows = $query->fetchAll(PDO::FETCH_ASSOC);
		$connectors = [];
	
		foreach ($rows as $row){
			$connector = new ConnectorBase();
			$connector->patch($row);
			unset($connector->dirty);
			if ($single) return $connector;
			$connectors[$connector->id] = $connector;
		}
		if ($single) return null;
		return $connectors;
	}
	
	/** instance functions **/
	public function __construct(){
		$this->patch(['direction'=>0]);
	}
	
	public function save(){
		$db = get_or_create_db();
		if (isset($this->id)){
			if (!empty($this->dirty)){
				$sql = 'UPDATE connectors SET';
				$args = [':id'=>$this->id];
	
				foreach ($this->dirty as $field){
					if (array_key_exists($field, ConnectorBase::fields())){
						$sql .= ' '.$field.'=:'.$field.',';
						$args[':'.$field] = $this->{$field};
					}
				}
				if (count($args)>1){
					$sql = rtrim($sql,',').' WHERE id = :id';
					$query = $db->prepare($sql);
					assert($query->execute($args),'Was no able to update connector in database!');
				}
			}
		} else {
			$known_fields = array_keys(ConnectorBase::fields());
			$fields = ['id'];
			$args = [':id'=>$this->name];
			foreach ($known_fields as $f){
				if (isset($this->{$f})){
					$fields[]=$f;
					$args[':'.$f] = $this->{$f};
				}
			}
			$query = $db->prepare('INSERT INTO connectors ( '.implode(', ',$fields).' ) VALUES ( :'.implode(', :',$fields).' )');
			assert($query->execute($args),'Was not able to insert new base connector');
			$this->id = $this->name;
			unset($this->name);
		}
		unset($this->dirty);
	}
	
	public function turn(){
		$this->patch(['direction' => 1-$this->direction]);
		$this->save();
	}
}

class Connector extends BaseClass{
	const DIR_IN = 0;
	const DIR_OUT = 1;
	
	/* static functions */
	static function fields(){
		return [
			'id' => ['INTEGER','KEY'=>'PRIMARY'],
			'model_id' => ['INT','NOT NULL'],
			'connector_id' => ['VARCHAR'=>255,'NOT NULL'],
			'process_instance_id' => ['INT','NOT NULL'],
			'angle'=>['INT'],
		];
	}

	static function load($options = []){
		$db = get_or_create_db();
		assert(isset($options['model_id']),'No model id passed to Connector::load()!');

		$sql = 'SELECT * FROM connector_instances';
		$where = ['model_id = ?'];
		$args = [$options['model_id']];
		
		if (isset($options['process_instance'])){
			$where[] = 'process_instance_id = ?';
			$args[] = $options['process_instance'];
		}

		$single = false;
		if (isset($options['ids'])){
			$ids = $options['ids'];
			if (!is_array($ids)) {
				$single = true;
				$ids = [$ids];
			}
			$qMarks = str_repeat('?,', count($ids)-1).'?';
			$where[] = 'id IN ('.$qMarks.')';
			$args = array_merge($args, $ids);
		}
		
		$sql .= ' WHERE '.implode(' AND ',$where);
		$query = $db->prepare($sql);
		
		assert($query->execute($args),'Was not able to load connectors');
		$rows = $query->fetchAll(PDO::FETCH_ASSOC);
		$connectors = [];

		foreach ($rows as $row){
			$connector = new Connector();
			$connector->base = ConnectorBase::load(['model_id'=>$options['model_id'],'ids'=>$row['connector_id']]);
			$connector->patch($row);
			unset($connector->dirty);
			if ($single) return $connector;
			$connectors[$connector->id] = $connector;
		}

		return $connectors;
	}

	/* instance methods */
	function delete(){
		foreach ($this->flows() as $flow) $flow->delete();
		$db = get_or_create_db();
		$query = $db->prepare('DELETE FROM connector_instances WHERE id = :id');
		$args = [':id'=>$this->id];
		assert($query->execute($args),t('Was not able to remove connector instance "?" from database.',$this->base->id));
	}
	
	function flows(){
		if (!isset($this->flows)) $this->flows = Flow::load(['model_id'=>$this->model_id,'connector'=>$this->id]);
		return $this->flows;
	}

	public function save(){
		$db = get_or_create_db();
		if (isset($this->id)){
			if (!empty($this->dirty)){
				$sql = 'UPDATE connector_instances SET';
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
			if (!isset($this->angle)) $this->angle = $this->select_angle();
			foreach ($known_fields as $f){
				if (isset($this->{$f})){
					$fields[]=$f;
					$args[':'.$f] = $this->{$f};
				}
			}
			$query = $db->prepare('INSERT INTO connector_instances ( '.implode(', ',$fields).' ) VALUES ( :'.implode(', :',$fields).' )');
			assert($query->execute($args),'Was not able to insert new connector');

			$this->id = $db->lastInsertId();
		}
		unset($this->dirty);
	}
	
	public function select_angle(){
		$connectors = Connector::load(['model_id'=>$this->model_id,'process_instance'=>$this->process_instance_id]);
		$angles = [];
		foreach ($connectors as $conn) $angles[] = $conn->angle;
		$angle = 0;
		while (in_array($angle,$angles)){
			switch ($angle){
				case 375:
					return 0;
					break;
				case 365:
					$angle = 15;
					break;
				case 370:
					$angle = 5;
					break;
				case 360:
					$angle = 10;
					break;
				default:
					$angle += 20;
			}
		}
		return $angle;
	}
}

class FlowBase extends BaseClass{
	/** static methods **/
	static function fields(){
		return [
			'id' => ['VARCHAR'=>255, 'NOT NULL'],
			'model_id' => ['INT','NOT NULL'],
			'description' => ['TEXT'],
			'definition' => ['TEXT'],
			'PRIMARY KEY'=>'(id, model_id)',
		];
	}
	
	static function load($options = []){
		$db = get_or_create_db();
		
		assert(isset($options['model_id']),'No model_id passed to FlowBase::load()!');
		
		$sql = 'SELECT * FROM flows';
		$where = ['model_id = ?'];
		$args = [$options['model_id']];
		
		$single = false;
		if (isset($options['ids'])){
			$ids = $options['ids'];
			if (!is_array($ids)) {
				$single = true;
				$ids = [$ids];
			}
			$qMarks = str_repeat('?,', count($ids)-1).'?';
			$where[] = 'id IN ('.$qMarks.')';
			$args = array_merge($args, $ids);
		}
	
		$query = $db->prepare($sql.' WHERE '.implode(' AND ',$where));
		assert($query->execute($args),'Was not able to load flows');
		$rows = $query->fetchAll(PDO::FETCH_ASSOC);
		$flows = [];
	
		foreach ($rows as $row){
			$flow = new FlowBase();
			$flow->patch($row);
			unset($flow->dirty);
			if ($single) return $flow;
			$flows[$flow->id] = $flow;
		}
		if ($single) return null;
		return $flows;
	}
	
	/** instance functions **/
	public function save(){
		$db = get_or_create_db();
		if (isset($this->id)){
			if (!empty($this->dirty)){
				$sql = 'UPDATE flows SET';
				$args = [':id'=>$this->id];
				foreach ($this->dirty as $field){
					if (array_key_exists($field, FlowBase::fields())){
						$sql .= ' '.$field.'=:'.$field.',';
						$args[':'.$field] = $this->{$field};
					} elseif ($field == 'name'){
						$sql .= ' id = :new_id,';
						$args[':new_id'] = $this->{$field};
						$this->update_references($this->{$field});
					}
						
				}
				$sql = rtrim($sql,',').' WHERE id = :id';
				debug(query_insert($sql,$args));
				$query = $db->prepare($sql);
				assert($query->execute($args),'Was no able to update flow in database!');
			}
		} else {
			$known_fields = array_keys(FlowBase::fields());
			$fields = ['id'];
			$args = [':id'=>$this->name];
			foreach ($known_fields as $f){
				if (isset($this->{$f})){
					$fields[]=$f;
					$args[':'.$f] = $this->{$f};
				}
			}
			$query = $db->prepare('INSERT INTO flows ( '.implode(', ',$fields).' ) VALUES ( :'.implode(', :',$fields).' )');
			assert($query->execute($args),'Was not able to insert new flow');
			$this->id = $this->name;
			unset($this->name);
		}
		unset($this->dirty);
	}
	
	private function update_references($new_id){
		$instances = Flow::load(['model_id'=>$this->model_id,'flow_id'=>$this->id]);
		foreach ($instances as $flow){
			$flow->patch(['flow_id'=>$new_id]);
			$flow->save();
		}
	}
}

class Flow extends BaseClass{
	/** static **/
	const TO_CONNECTOR = 0;
	const TO_TERMINAL = 1;
	const TO_SIBLING = 2;
	
	static function fields(){
		return [
			'id' => ['INTEGER','KEY'=>'PRIMARY'],
			'model_id' => ['INT','NOT NULL'],
			'flow_id' => ['VARCHAR'=>255,'NOT NULL'],
			'start_connector' => ['INT'], // null for terminals
			'start_terminal' => ['INT'], // null for connector
			'end_connector' => ['INT'], // null for terminals
			'end_terminal' => ['INT'], // null for connector
		];
	}
	
	static function load($options = []){
		$db = get_or_create_db();
		assert(isset($options['model_id']),'No model_id passed to Flow::load()!');
		
		$sql = 'SELECT * FROM flow_instances';
		$where = ['model_id = ?'];
		$args = [$options['model_id']];

		if (isset($options['connector'])){
			$where[] = '(start_connector = ?  OR end_connector = ?)';
			$args = array_merge($args, [$options['connector'],$options['connector']]);
		}

		$single = false;
		if (isset($options['ids'])){
			$ids = $options['ids'];
			if (!is_array($ids)) {
				$single = true;
				$ids = [$ids];
			}
			$qMarks = str_repeat('?,', count($ids)-1).'?';
			$where[] = 'id IN ('.$qMarks.')';
			$args = array_merge($args, $ids);
		}
		
		if (isset($options['flow_id'])){
			$where[] = 'flow_id = ?';
			$args[] = $options['flow_id'];
		}

		$query = $db->prepare($sql.' WHERE '.implode(' AND ',$where));
		assert($query->execute($args),'Was not able to load flows');
		$rows = $query->fetchAll(PDO::FETCH_ASSOC);
		$flows = [];

		foreach ($rows as $row){
			$flow = new Flow();
			$flow->base = FlowBase::load(['model_id'=>$options['model_id'],'ids'=>$row['flow_id']]);
			$flow->patch($row);
			unset($flow->dirty);
			if ($single) return $flow;
			$flows[$flow->id] = $flow;
		}
		if ($single) return null;
		return $flows;
	}

	/** instance methods **/
	function delete(){
		$db = get_or_create_db();
		$query = $db->prepare('DELETE FROM flow_instances WHERE id = :id');
		$args = [':id'=>$this->id];
		assert($query->execute($args),t('Was not able to remove flow "?" from database.',$this->name));
	}
	
	public function save(){
		$db = get_or_create_db();
		if (isset($this->id)){
			if (!empty($this->dirty)){
				$sql = 'UPDATE flow_instances SET';
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
			$query = $db->prepare('INSERT INTO flow_instances ( '.implode(', ',$fields).' ) VALUES ( :'.implode(', :',$fields).' )');
			assert($query->execute($args),'Was not able to insert new flow');

			$this->id = $db->lastInsertId();
		}
		unset($this->dirty);
	}
}

class Model extends BaseClass{
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

		if (isset($options['project_id'])){
			if (in_array($options['project_id'], $project_ids)) {
				$project_ids = [$options['project_id']];
			} else {
				error('You are not allowed to access the project (?)!',$options['project_id']);
				return null;
			}
		}

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
	
	public function connectors($id = null){
		if (!isset($this->connectors)) $this->connectors = ConnectorBase::load(['model_id'=>$this->id]);
		if ($id) return $this->connectors[$id];
		return $this->connectors;
	}
	
	public function connector_instances($id = null){
		if (!isset($this->connector_instances)) $this->connector_instances = Connector::load(['model_id'=>$this->id, 'parent'=>null]);
		if ($id) return $this->connector_instances[$id];
		return $this->connector_instances;
	}

	function delete(){
		$db = get_or_create_db();

		foreach ($this->processes() as $proc) $proc->delete($this->id);
		foreach ($this->terminals() as $term) $term->delete();

		$query = $db->prepare('DELETE FROM models WHERE id = :id');
		$args = [':id'=>$this->id];
		debug(query_insert($query,$args));
		assert($query->execute($args),t('Was not able to remove model "?" from database.',$this->name));
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

	public function processes($id = null){
		if (!isset($this->processes)) $this->processes = ProcessBase::load(['model_id'=>$this->id]);
		if ($id) return $this->processes[$id];
		return $this->processes;
	}

	public function process_instances($id = null){
		if (!isset($this->process_instances)) $this->process_instances = Process::load(['model_id'=>$this->id, 'parent'=>null]);
		if ($id) return $this->process_instances[$id];
		return $this->process_instances;
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
		if (!isset($this->terminals)) $this->terminals = TerminalBase::load(['model_id'=>$this->id]);
		if ($id) return $this->terminals[$id];
		return $this->terminals;
	}

	public function terminal_instances($id = null){
		if (!isset($this->terminal_instances)) $this->terminal_instances = Terminal::load(['model_id'=>$this->id]);
		if ($id) return $this->terminal_instances[$id];
		return $this->terminal_instances;
	}

	public function url(){
		return getUrl('model',$this->id.'/view');
	}
}

class ProcessBase extends BaseClass{
	/** static functions **/
	static function fields(){
		return [
			'id' => ['VARCHAR'=>255, 'NOT NULL'],
			'model_id' => ['INT','NOT NULL'],
			'description' => 'TEXT',
			'r' => ['INT','DEFAULT 30'],
			'PRIMARY KEY'=>'(id, model_id)',
		];
	}

	static function load($options = []){
		$db = get_or_create_db();

		assert(isset($options['model_id']),'No model id passed to ProcessBase::load()!');

		$where = ['model_id = ?'];
		$args = [$options['model_id']];

		$sql = 'SELECT * FROM processes WHERE ';

		$single = false;
		if (isset($options['ids'])){
			$ids = $options['ids'];
			if (!is_array($ids)) {
				$single = true;
				$ids = [$ids];
			}
			$qMarks = str_repeat('?,', count($ids)-1).'?';
			$where[] = 'id IN ('.$qMarks.')';
			$args = array_merge($args, $ids);
		}
		
		$sql .= implode(' AND ', $where);

		$query = $db->prepare($sql);
		assert($query->execute($args),'Was not able to load processes');
		$rows = $query->fetchAll(PDO::FETCH_ASSOC);

		$bases = [];
		foreach ($rows as $row){
			$base = new ProcessBase($row['id']);
			$base->patch($row);
			unset($base->dirty);
			if ($single) return $base;
			$bases[$row['id']] = $base;
		}
		if ($single) return null;
		return $bases;
	}

	/** instance functions **/
	public function __construct(){
		$this->patch(['r'=>50]);
	}
	
	public function children(){
		return Process::load(['model_id'=>$this->model_id,'parent'=>$this->id]);
	}
	
	public function save(){
		$db = get_or_create_db();
		if (isset($this->id)){
			if (!empty($this->dirty)){
				$sql = 'UPDATE processes SET';
				$args = [':id'=>$this->id];
								
				foreach ($this->dirty as $field){
					if (array_key_exists($field, ProcessBase::fields())){
						$sql .= ' '.$field.'=:'.$field.',';
						$args[':'.$field] = $this->{$field};
					} elseif ($field == 'name'){
						$sql .= ' id = :new_id,';
						$args[':new_id'] = $this->{$field};
						$this->update_references($this->{$field});
					}
				}
				if (count($args)>1){
					$sql = rtrim($sql,',').' WHERE id = :id';
					$query = $db->prepare($sql);
					assert($query->execute($args),'Was no able to update process in database!');
				}
			}
		} else {
			$known_fields = array_keys(ProcessBase::fields());
			$fields = ['id'];
			$args = [':id'=>$this->name];
			foreach ($known_fields as $f){
				if (isset($this->{$f})){
					$fields[]=$f;
					$args[':'.$f] = $this->{$f};
				}
			}
			$query = $db->prepare('INSERT INTO processes ( '.implode(', ',$fields).' ) VALUES ( :'.implode(', :',$fields).' )');
			assert($query->execute($args),'Was not able to insert new base process');
			$this->id = $this->name;
			unset($this->name);
		}
		unset($this->dirty);
	}
	
	public function update_references($new_id){		
		$process_instances = Process::load(['model_id'=>$this->model_id,'process_id'=>$this->id]);
		$connectors = ConnectorBase::load(['model_id'=>$this->model_id,'process_id'=>$this->id]);
		$children = Process::load(['model_id'=>$this->model_id,'parent'=>$this->id]);
		foreach ($process_instances as $process){
			$process->patch(['process_id'=>$new_id]);
			$process->save();
		}
		foreach ($children as $process){
			$process->patch(['parent'=>$new_id]);
			$process->save();
		}
		
		foreach ($connectors as $connector){
			$connector->patch(['process_id'=>$new_id]);
			$connector->save();
		}
	}
}

class Process extends BaseClass{
	/** static functions **/
	static function fields(){
		return [
			'id' => ['INTEGER','KEY'=>'PRIMARY'],
			'model_id' => ['INT','NOT NULL'],
			'process_id'=> ['VARCHAR'=>255, 'NOT NULL'],
			'parent' => 'TEXT',
			'x' => ['INT', 'DEFAULT 30'],
			'y' => ['INT', 'DEFAULT 30'],
		];
	}

	static function load($options = []){

		assert(isset($options['model_id']),'No model id passed to Process::load()!');
		$bases = ProcessBase::load(['model_id' => $options['model_id']]);

		$db = get_or_create_db();

		$where = ['model_id = ?'];
		$args = [$options['model_id']];

		$sql = 'SELECT * FROM process_instances WHERE ';

		$single = false;
		if (isset($options['ids'])){
			$ids = $options['ids'];
			if (!is_array($ids)) {
				$single = true;
				$ids = [$ids];
			}
			$qMarks = str_repeat('?,', count($ids)-1).'?';
			$where[] = 'id IN ('.$qMarks.')';
			$args = array_merge($args, $ids);
		}
		if (array_key_exists('parent',$options)){
			if ($options['parent'] === null){
				$where[] = 'parent IS NULL';
			} else {
				$where[] = 'parent = ?';
				$args[] = $options['parent'];
			}
		}
		
		if (isset($options['process_id'])){
			$where[] = 'process_id = ?';
			$args[] = $options['process_id'];
		}
		
		$sql .= implode(' AND ', $where);

		$query = $db->prepare($sql);
		assert($query->execute($args),'Was not able to load processes');
		$rows = $query->fetchAll(PDO::FETCH_ASSOC);

		$processes = [];
		foreach ($rows as $row){
			$process = new Process($row['id']);
			$process->base = $bases[$row['process_id']];
			$process->patch($row);
			unset($process->dirty);
			if ($single) return $process;
			$processes[$row['id']] = $process;
		}
		if ($single) return null;
		return $processes;
	}

	/** instance functions **/
	function addChild($child_process_id){
		$db = get_or_create_db();
		$sql = 'INSERT INTO child_processes (process_id, parent_process) VALUES (:child_id, :parent_id)';
		$args = [':child_id' => $child_process_id, ':parent_id' => $this->id];
		$query = $db->prepare($sql);
		assert($query->execute($args),'Was not able to assign child to process');
	}
	
	function add_connector_instances($base_connectors){
		foreach ($base_connectors as $base){
			$connector = new Connector();
			$connector->base = $base;
			$connector->patch(['model_id'=>$this->model_id,'connector_id'=>$base->id,'process_instance_id'=>$this->id,'angle'=>180*$base->direction]);
			$connector->save();
		}
	}

	function children($id = null){
		if (!isset($this->children)) $this->children = Process::load(['model_id'=>$this->model_id,'parent'=>$this->process_id]);
		if ($id) return $this->children[$id];
		return $this->children;
	}

	function connectors($id = null){
		if (!isset($this->connectors)) {
			$base_connectors = ConnectorBase::load(['model_id'=>$this->model_id,'process_id'=>$this->base->id]);			
			$this->connectors = Connector::load(['model_id'=>$this->model_id,'process_instance'=>$this->id]);
			foreach ($this->connectors as $conn) unset($base_connectors[$conn->connector_id]);
			if (!empty($base_connectors)) {
				$this->add_connector_instances($base_connectors);
				$this->connectors = Connector::load(['model_id'=>$this->model_id,'process_instance'=>$this->id]);
			}
		}
		if ($id) {
			if (array_key_exists($id, $this->connectors)) return $this->connectors[$id];
			return null;
		}
		return $this->connectors;
	}
	
	function delete($model_id){
		foreach ($this->children() as $child_process) $child_process->delete($model_id);
		foreach ($this->connectors() as $conn) $conn->delete($model_id);
		$db = get_or_create_db();
		$query = $db->prepare('DELETE FROM processes WHERE id = :id');
		$args = [':id'=>$this->id];
		debug(query_insert($query,$args));
		assert($query->execute($args),t('Was not able to remove process "?" from database.',$this->name));
		
		$query = $db->prepare('DELETE FROM child_processes WHERE process_id = :id');
		$args = [':id'=>$this->id];
		debug(query_insert($query,$args));
		assert($query->execute($args),t('Was not able to remove process "?" from database.',$this->name));
		
		$query = $db->prepare('DELETE FROM models_processes WHERE process_id = :id');
		$args = [':id'=>$this->id];
		debug(query_insert($query,$args));
		assert($query->execute($args),t('Was not able to remove process "?" from models_processes table.',$this->name));
	}
	
	function parent_process(){
		if ($this->parent) return ProcessBase::load(['model_id'=>$this->model_id,'ids'=>$this->parent]);
		return null;
	}

	function patch($data = array()){
		foreach ($data as $key => $val){
			if ($key === 'r') {
				$this->base->patch([$key => $val]);
			} else parent::patch([$key => $val]);
		}
	}

	public function save(){
		$db = get_or_create_db();
		if (isset($this->base)) $this->base->save();
		if (isset($this->id)){
			if (!empty($this->dirty)){
				$sql = 'UPDATE process_instances SET';
				$args = [':id'=>$this->id];

				foreach ($this->dirty as $field){
					if (array_key_exists($field, Process::fields())){
						$sql .= ' '.$field.'=:'.$field.',';
						$args[':'.$field] = $this->{$field};
					}
				}
				if (count($args)>1){
					$sql = rtrim($sql,',').' WHERE id = :id';
					$query = $db->prepare($sql);
					assert($query->execute($args),'Was no able to update process in database!');
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
			$query = $db->prepare('INSERT INTO process_instances ( '.implode(', ',$fields).' ) VALUES ( :'.implode(', :',$fields).' )');
			assert($query->execute($args),'Was not able to insert new process');
			$this->id = $db->lastInsertId();
		}
		unset($this->dirty);
	}

	function svg(&$model, &$parent = null){
		$this->path = $this->id;
		if (isset($parent)){
			$rad = $parent->base->r;
			$this->x = $this->x < -$rad ? -$rad : ($this->x > $rad ? $rad : $this->x);
			$this->y = $this->y < -$rad ? -$rad : ($this->y > $rad ? $rad : $this->y);
			$this->path = $parent->path.'.'.$this->path;
		} else {
			$this->x = $this->x < 0 ? 0 : ($this->x > 1000 ? 1000 : $this->x);
			$this->y = $this->y < 0 ? 0 : ($this->y > 1000 ? 1000 : $this->y);
		}
		
		?>
		<g class="process" transform="translate(<?= $this->x ?>,<?= $this->y ?>)">
			<circle
					class="process"
					cx="0"
					cy="0"
					r="<?= $this->base->r ?>"
					id="process_<?= $this->id ?>">
				<title><?= $this->base->description ?><?= "\n".t('Use Shift+Mousewheel to alter size')?></title>
			</circle>
			<text x="0" y="0"><title><?= $this->base->description ?><?= "\n".t('Use Shift+Mousewheel to alter size')?></title><?= $this->process_id ?></text>
			<?php foreach ($this->connectors() as $conn){
				foreach ($conn->flows() as $flow){
					if ($flow->start_connector == null){
						$terminal = $model->terminal_instances($flow->start_terminal);
						$x2 =  sin($conn->angle*RAD)*$this->base->r;
						$y2 = -cos($conn->angle*RAD)*$this->base->r;

						$x1 = -$this->x + $terminal->x + $terminal->base->w/2;
						$y1 = -$this->y + $terminal->y + ($terminal->y > $y2 ? 0 : 30);

						$proc_pointer = $parent;
						while ($proc_pointer){
							$x1 -= $proc_pointer->x;
							$y1 -= $proc_pointer->y;
							$proc_pointer = $proc_pointer->parent;
						}

						arrow($x1,$y1,$x2,$y2,$flow->base->id,getUrl('model',$model->id.'/flow/'.$flow->id));
						continue;
					}

					if ($flow->end_connector == null){
						$terminal = $model->terminal_instances($flow->end_terminal);

						$x1 = + sin($conn->angle*RAD)*$this->base->r;
						$y1 = - cos($conn->angle*RAD)*$this->base->r;

						$x2 = -$this->x + $terminal->x + $terminal->base->w/2;
						$y2 = -$this->y + $terminal->y + ($terminal->y > $y1 ? 0 : 30);

						$proc_pointer = $parent;
						while ($proc_pointer){
							$x2 -= $proc_pointer->x;
							$y2 -= $proc_pointer->y;
							$proc_pointer = $proc_pointer->parent;
						}

						arrow($x1,$y1,$x2,$y2,$flow->base->id,getUrl('model',$model->id.'/flow/'.$flow->id));
						continue;
					}

					if ($conn->base->direction){ // OUT
						if ($flow->start_connector != $conn->id) continue;
						$end_connector = $parent->connectors($flow->end_connector);
						
						if ($end_connector){ // flow goes to connector of parent
							$x1 = $this->base->r*sin($conn->angle*RAD);
							$y1 = -$this->base->r*cos($conn->angle*RAD);

							$x2 = -$this->x + $parent->base->r * sin($end_connector->angle*RAD);
							$y2 = -$this->y - $parent->base->r * cos($end_connector->angle*RAD);

							arrow($x1,$y1,$x2,$y2,$flow->base->id,getUrl('model',$model->id.'/flow/'.$flow->id));
						}
					} else { // IN
						if ($flow->end_connector != $conn->id) continue;
						$start_connector = $parent->connectors($flow->start_connector);
						if ($start_connector === null){ // flow comes from sobling of process
							foreach ($parent->children() as $sibling){
								$start_connector = $sibling->connectors($flow->start_connector);
								if ($start_connector) break;
							}
							$x1 = -$this->x + $sibling->x + $sibling->base->r * sin($start_connector->angle*RAD);
							$y1 = -$this->y + $sibling->y - $sibling->base->r * cos($start_connector->angle*RAD);

							$x2 = $this->base->r*sin($conn->angle*RAD);
							$y2 = -$this->base->r*cos($conn->angle*RAD);

							arrow($x1,$y1,$x2,$y2,$flow->base->id,getUrl('model',$model->id.'/flow/'.$flow->id));
						} else { // flow comes from connector of parent
							$x1 = -$this->x + $parent->base->r * sin($start_connector->angle*RAD);
							$y1 = -$this->y - $parent->base->r * cos($start_connector->angle*RAD);;

							$x2 = $this->base->r*sin($conn->angle*RAD);
							$y2 = -$this->base->r*cos($conn->angle*RAD);

							arrow($x1,$y1,$x2,$y2,$flow->base->id,getUrl('model',$model->id.'/flow/'.$flow->id));
						}
					}
				} // foreach flow */
			?>
			<a xlink:href="connect_<?= $conn->base->direction == Connector::DIR_IN ? 'in':'out' ?>/<?= $conn->id ?>">
				<circle
						class="connector"
						cx="0"
						cy="<?= -$this->base->r ?>"
						r="15"
						id="connector_<?= $conn->id ?>"
						transform="rotate(<?= $conn->angle ?>,0,0)">
					<title><?= $conn->base->id ?></title>
				</circle>
			</a>
			<?php } // foreach connector
			foreach ($this->children() as $child) $child->svg($model,$this);
			?>
		</g>
	<?php }
}

class TerminalBase extends BaseClass{
	const TERMINAL = 0;
	const DATABASE = 1;
	
	/** static functions **/
	static function fields(){
		return [
			'id' => ['VARCHAR'=>255, 'NOT NULL'],
			'model_id' => ['INT','NOT NULL'],
			'type' => 'INT',
			'description' => 'TEXT',
			'w' => ['INT','DEFAULT 50'],
			'PRIMARY KEY' => '(id, model_id)',
		];
	}
	
	static function load($options = []){
		$db = get_or_create_db();
	
		assert(isset($options['model_id']),'No model id passed to TerminalBase::load()!');
	
		$where = ['model_id = ?'];
		$args = [$options['model_id']];
		
		$sql = 'SELECT * FROM terminals WHERE ';
		
		$single = false;
		if (isset($options['ids'])){
			$ids = $options['ids'];
			if (!is_array($ids)) {
				$single = true;
				$ids = [$ids];
			}
			$qMarks = str_repeat('?,', count($ids)-1).'?';
			$where[] = 'id IN ('.$qMarks.')';
			$args = array_merge($args, $ids);
		}
		$sql .= implode(' AND ', $where);
		
		$query = $db->prepare($sql);
		assert($query->execute($args),'Was not able to load terminals');
		$rows = $query->fetchAll(PDO::FETCH_ASSOC);

		$bases = [];
		foreach ($rows as $row){
			$base = new TerminalBase($row['id']);
			$base->patch($row);
			unset($base->dirty);
			if ($single) return $base;
			$bases[] = $base;
		}
		if ($single) return null;
		return $bases;
	}
	
	/** instance functions **/
	public function __construct(){
		$this->patch(['w'=>50]);
	}
	
	public function save(){
		$db = get_or_create_db();
		if (isset($this->id)){
			if (!empty($this->dirty)){
				$sql = 'UPDATE terminals SET';
				$args = [':id'=>$this->id];
	
				foreach ($this->dirty as $field){
					if (array_key_exists($field, TerminalBase::fields())){
						$sql .= ' '.$field.'=:'.$field.',';
						$args[':'.$field] = $this->{$field};
					}
				}
				if (count($args)>1){
					$sql = rtrim($sql,',').' WHERE id = :id';
					$query = $db->prepare($sql);
					assert($query->execute($args),'Was no able to update terminal in database!');
				}
			}
		} else {
			$known_fields = array_keys(TerminalBase::fields());
			$fields = ['id'];
			$args = [':id'=>$this->name];
			foreach ($known_fields as $f){
				if (isset($this->{$f})){
					$fields[]=$f;
					$args[':'.$f] = $this->{$f};
				}
			}
			$query = $db->prepare('INSERT INTO terminals ( '.implode(', ',$fields).' ) VALUES ( :'.implode(', :',$fields).' )');
			assert($query->execute($args),'Was not able to insert new terminal');
			$this->id = $this->name;
			unset($this->name);
		}
		unset($this->dirty);
	}
}

class Terminal extends BaseClass{
	
	/** static functions **/
	static function fields(){
		return [
			'id' => ['INTEGER','KEY'=>'PRIMARY'],
			'model_id' => ['INT','NOT NULL'],
			'terminal_id' => ['VARCHAR'=>255, 'NOT NULL'],
			'x' => ['INT','DEFAULT 0'],
			'y' => ['INT','DEFAULT 0'],
		];
	}

	static function load($options = []){

		assert(isset($options['model_id']),'No model id passed to Process::load()!');
		$bases = TerminalBase::load(['model_id'=>$options['model_id']]);
		$db = get_or_create_db();

		$terminals = [];

		foreach ($bases as $base){
			$query = $db->prepare('SELECT * FROM terminal_instances WHERE model_id = :model AND terminal_id = :tid');
			$args = [':model'=>$options['model_id'],':tid'=>$base->id];
			assert($query->execute($args),'Was not able to load terminals');
			$rows = $query->fetchAll(PDO::FETCH_ASSOC);
			foreach ($rows as $row){
				$terminal = new Terminal();
				$terminal->patch($row);
				$terminal->dirty = [];
				$terminal->base = $base;
				$terminals[$terminal->id] = $terminal;
			}
		}
		return $terminals;
	}

	static function load_bases($options = []){
		$db = get_or_create_db();

		assert(isset($options['model_id']),'No model id passed to Process::load()!');

		$sql = 'SELECT * FROM terminals WHERE model_id = :model';
		$args = [':model'=>$options['model_id']];
		$query = $db->prepare($sql);
		assert($query->execute($args),'Was not able to load terminals');
		$bases = $query->fetchAll(PDO::FETCH_ASSOC);
		
		$terminals = [];
		
		foreach ($bases as $base){
			$query = $db->prepare('SELECT * FROM terminal_instances WHERE model_id = :model AND terminal_id = :tid');
			$args = [':model'=>$options['model_id'],':tid'=>$base['id']];
			assert($query->execute($args),'Was not able to load terminals');
			$rows = $query->fetchAll(PDO::FETCH_ASSOC);
			foreach ($rows as $row){
				$terminal = new Terminal();
				$terminal->patch($row);
				$terminal->dirty = [];
				$terminal->base = $base;
				$terminals[$terminal->id] = $terminal;
			}
		}
		return $terminals;
	}

	/** instance functions **/	
	function delete(){
		$db = get_or_create_db();
		$query = $db->prepare('DELETE FROM terminals WHERE id = :id');
		$args = [':id'=>$this->id];
		debug(query_insert($query,$args));
		assert($query->execute($args),t('Was not able to remove terminal "?" from terminals table.',$this->name));
		
		$query = $db->prepare('DELETE FROM models_terminals WHERE terminal_id = :id');
		$args = [':id'=>$this->id];
		debug(query_insert($query,$args));
		assert($query->execute($args),t('Was not able to remove terminal "?" from models_terminals table.',$this->name));
		
		$query = $db->prepare('DELETE FROM flows WHERE ( start_process IS NULL AND start_id = :id ) OR ( end_process IS NULL AND end_id = :id )');
		$args = [':id'=>$this->id];
		debug(query_insert($query,$args));
		assert($query->execute($args),t('Was not able to remove flows from/to terminal "?" from database.',$this->name));

	}

	function patch($data = array()){
		foreach ($data as $key => $val){
			if ($key === 'w') {
				$this->base->patch([$key => $val]);
			} else parent::patch([$key => $val]);
		}
	}

	public function save(){
		$db = get_or_create_db();
		if (isset($this->base)) $this->base->save();
		if (isset($this->id)){
			if (!empty($this->dirty)){
				$sql = 'UPDATE terminal_instances SET';
				$args = [':id'=>$this->id];

				foreach ($this->dirty as $field){
					if (array_key_exists($field, Terminal::fields())){
						$sql .= ' '.$field.'=:'.$field.',';
						$args[':'.$field] = $this->{$field};
					}
				}
				if (count($args)>1){
					$sql = rtrim($sql,',').' WHERE id = :id';
					$query = $db->prepare($sql);
					assert($query->execute($args),'Was no able to update terminal in database!');
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
			$query = $db->prepare('INSERT INTO terminal_instances ( '.implode(', ',$fields).' ) VALUES ( :'.implode(', :',$fields).' )');
			assert($query->execute($args),'Was not able to insert new terminal');
			$this->id = $db->lastInsertId();
		}
		unset($this->dirty);
	}

	public function svg(){ ?>
	<g transform="translate(<?= $this->x>0?$this->x:0 ?>,<?= $this->y>0?$this->y:0 ?>)">
		<?php if (!$this->base->type) { // terminal ?>
		<rect
				class="terminal"
				x="0"
				y="0"
				width="<?= $this->base->w ?>"
				height="30"
				id="terminal_<?= $this->id ?>">
			<title><?= $this->base->description ?></title>
		</rect>
		<text x="<?= $this->base->w/2 ?>" y="15" fill="red"><title><?= $this->base->description ?></title><?= $this->base->id ?></text>
		<?php } else { ?>
		<ellipse
				 cx="<?= $this->base->w/2 ?>"
				 cy="40"
				 rx="<?= $this->base->w/2?>"
				 ry="15">
			<title><?= $this->base->description ?></title>
		</ellipse>
		<rect
				class="terminal"
				x="0"
				y="0"
				width="<?= $this->base->w ?>"
				height="40"
			  	stroke-dasharray="0,<?= $this->base->w ?>,40,<?= $this->base->w ?>,40"
				id="terminal_<?= $this->id ?>">
			<title><?= $this->base->description ?></title>
		</rect>
		<ellipse
				 cx="<?= $this->base->w/2 ?>"
				 cy="0"
				 rx="<?= $this->base->w/2?>"
				 ry="15">
			<title><?= $this->base->description ?></title>
		</ellipse>
		<text x="<?= $this->base->w/2 ?>" y="30" fill="red"><title><?= $this->base->description ?></title><?= $this->base->id ?></text>
		<?php } ?>
	</g>
	<?php }
}
