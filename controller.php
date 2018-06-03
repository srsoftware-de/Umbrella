<?php

const RAD = 0.01745329;

function get_or_create_db(){
	if (!file_exists('db')) assert(mkdir('db'),'Failed to create model/db directory!');
	assert(is_writable('db'),'Directory model/db not writable!');
	if (!file_exists('db/models.db')){
		$db = new PDO('sqlite:db/models.db');

		$tables = [
			'flows'=>    Flow::fields(),
			'flow_instances'=> FlowInstance::fields(),
			'connectors'=> Connector::fields(),
			'connector_instances'=> ConnectorInstance::fields(),
			'models'=>   Model::fields(),
			'processes'=>Process::fields(),
			'process_instances'=>ProcessInstance::fields(),
			'terminals'=>Terminal::fields(),
			'terminal_instances'=>TerminalInstance::fields(),
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
			assert($db->query($sql),'Was not able to create '.$table.' table in models.db!');
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
	<?php  $dx = $x2 - $x1; $dy = $y2 - $y1; $alpha = ($dy == 0) ? 0 : atan($dx/$dy); if ($dy < 0) $alpha+=pi(); $x1 = $x2 - 25*sin($alpha+0.2); $y1 = $y2 - 25*cos($alpha+0.2); ?>
	<line x1="<?= $x1 ?>" y1="<?= $y1 ?>" x2="<?= $x2 ?>" y2="<?= $y2 ?>" />
	<?php $x1 = $x2 - 25*sin($alpha-0.2); $y1 = $y2 - 25*cos($alpha-0.2); ?>
	<line x1="<?= $x1 ?>" y1="<?= $y1 ?>" x2="<?= $x2 ?>" y2="<?= $y2 ?>" />
	<circle cx="<?= $x2-$dx/2 ?>" cy="<?= $y2-$dy/2 ?>" r="15" /><text x="<?= $x2-$dx/2 ?>" y="<?= $y2-$dy/2 ?>"><?= $text ?></text>
</g>
<?php if ($link){ ?></a><?php } 
}

function markdown($text){
	if (file_exists('../lib/parsedown/Parsedown.php')){
		include_once '../lib/parsedown/Parsedown.php';
		return Parsedown::instance()->parse($text);
	} else {
		return str_replace("\n", "<br/>", htmlentities($text));
	}
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

class Connector extends BaseClass{
	/* static functions */
	const DIR_IN = 0;
	const DIR_OUT = 1;
	
	static function fields(){
		return [
			'id' => ['VARCHAR'=>255, 'NOT NULL'],
			'project_id' => ['INT','NOT NULL'],
			'process_id' => ['INT','NOT NULL'],
			'direction' => ['BOOLEAN'],
			'PRIMARY KEY'=>'(id, project_id)',
		];
	}
	
	static function load($options = []){
		//debug(['method'=>'Connector::load','options'=>$options]);
		$sql = 'SELECT * FROM connectors';
		$where = [];
		$args = [];
	
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
		
		if (isset($options['project_id'])){
			$where[] = 'project_id = ?';
			$args[] = $options['project_id'];
		}

		$db = get_or_create_db();
		
		$query = $db->prepare($sql.' WHERE '.implode(' AND ',$where));
		//debug(query_insert($query,$args));
		assert($query->execute($args),'Was not able to load connectors');
		$rows = $query->fetchAll(PDO::FETCH_ASSOC);
		$connectors = [];
	
		foreach ($rows as $row){
			$connector = new Connector();
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
	
	public function delete(){
		$db = get_or_create_db();
		$query = $db->prepare('DELETE FROM connectors WHERE project_id = :pid AND id = :id');
		$args = [':pid'=>$this->project_id,':id'=>$this->id];
		debug(query_insert($query,$args));
		$query->execute($args);		
	}
	
	public function save(){
		$db = get_or_create_db();
		if (isset($this->id)){
			if (!empty($this->dirty)){
				$sql = 'UPDATE connectors SET';
				$args = [':id'=>$this->id];
	
				foreach ($this->dirty as $field){
					if (array_key_exists($field, Connector::fields())){
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
			$known_fields = array_keys(Connector::fields());
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

class ConnectorInstance extends BaseClass{
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

		$sql = 'SELECT * FROM connector_instances';
		$where = [];
		$args = [];
		
		if (isset($options['connector_id'])){
			$where[] = 'connector_id = ?';
			$args[] = $options['connector_id'];
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
		
		if (isset($options['model_id'])){
			$where[] = 'model_id = ?';
			$args[] = $options['model_id'];
		}
		
		if (isset($options['process_instance_id'])){
			$where[] = 'process_instance_id = ?';
			$args[] = $options['process_instance_id'];
		}
		
		if (isset($options['project_id'])){
			$where[] = 'model_id IN (SELECT id FROM models WHERE project_id = ?)';
			$args[] = $options['project_id'];
		}
		
		$sql .= ' WHERE '.implode(' AND ',$where);
		$query = $db->prepare($sql);
		
		assert($query->execute($args),'Was not able to load connectors');
		$rows = $query->fetchAll(PDO::FETCH_ASSOC);
		$connectors = [];

		foreach ($rows as $row){
			$connector = new ConnectorInstance();			
			$connector->base = Connector::load(['model_id'=>$row['model_id'],'ids'=>$row['connector_id']]);
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
		debug(query_insert($query,$args));
		assert($query->execute($args),t('Was not able to remove connector instance "?" from database.',$this->base->id));
		$other_instances = ConnectorInstance::load(['project_id'=>$this->base->project_id,'connector_id'=>$this->connector_id]);
		if (empty($other_instances)) $this->base->delete();		
	}
	
	function flows(){
		if (!isset($this->flows)) $this->flows = FlowInstance::load(['model_id'=>$this->model_id,'connector'=>$this->id]);
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
			$known_fields = array_keys(ConnectorInstance::fields());
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
		$connectors = ConnectorInstance::load(['model_id'=>$this->model_id,'process_instance_id'=>$this->process_instance_id]);
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

class Flow extends BaseClass{
	/** static methods **/
	const TO_CONNECTOR = 0;
	const TO_TERMINAL = 1;
	const TO_SIBLING = 2;
	
	static function fields(){
		return [
			'id' => ['VARCHAR'=>255, 'NOT NULL'],
			'project_id' => ['INT','NOT NULL'],
			'description' => ['TEXT'],
			'definition' => ['TEXT'],
			'PRIMARY KEY'=>'(id, project_id)',
		];
	}
	
	static function load($options = []){
		
		$sql = 'SELECT * FROM flows';
		$where = [];
		$args = [];
		
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
		
		if (isset($options['project_id'])){
			$where[] = 'project_id = ?';
			$args[] = $options['project_id'];
		}
		
		if (isset($options['model_id'])){
			$where[] = 'project_id = (SELECT project_id FROM models WHERE id = ?)';
			$args[] = $options['model_id'];
		}
	
		$db = get_or_create_db();
		$query = $db->prepare($sql.' WHERE '.implode(' AND ',$where));
		
		assert($query->execute($args),'Was not able to load flows');
		$rows = $query->fetchAll(PDO::FETCH_ASSOC);
		$flows = [];
		
		foreach ($rows as $row){
			$flow = new Flow();
			$flow->patch($row);
			unset($flow->dirty);
			if ($single) return $flow;
			$flows[$flow->id] = $flow;
		}
		if ($single) return null;
		return $flows;
	}
	
	/** instance functions **/
	public function delete(){
		$db = get_or_create_db();
		$query = $db->prepare('DELETE FROM flows WHERE project_id = :pid AND id = :id');
		$args = [':pid'=>$this->project_id,':id'=>$this->id];
		debug(query_insert($query,$args));
		$query->execute($args);
	}
	
	public function save(){
		$db = get_or_create_db();
		if (isset($this->id)){
			if (!empty($this->dirty)){
				$sql = 'UPDATE flows SET';
				$args = [':id'=>$this->id];
				foreach ($this->dirty as $field){
					if (array_key_exists($field, Flow::fields())){
						$sql .= ' '.$field.'=:'.$field.',';
						$args[':'.$field] = $this->{$field};
					} elseif ($field == 'name'){
						$sql .= ' id = :new_id,';
						$args[':new_id'] = $this->{$field};
						$this->update_references($this->{$field});
					}
						
				}
				$sql = rtrim($sql,',').' WHERE id = :id';
				$query = $db->prepare($sql);
				assert($query->execute($args),'Was no able to update flow in database!');
			}
		} else {
			$known_fields = array_keys(Flow::fields());
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
		$instances = FlowInstance::load(['model_id'=>$this->model_id,'flow_id'=>$this->id]);
		foreach ($instances as $flow){
			$flow->patch(['flow_id'=>$new_id]);
			$flow->save();
		}
	}
}

class FlowInstance extends BaseClass{
	/** static **/
	
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
		
		$sql = 'SELECT * FROM flow_instances';
		$where = [];		
		$args = [];

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
		
		$bases = [];
		if (isset($options['model_id'])){
			$where[] = 'model_id  = ?';
			$args[] = $options['model_id'];
			$bases = Flow::load(['model_id'=>$options['model_id']]);
		}
		
		if (isset($options['project_id'])){
			$where[] = 'model_id IN (SELECT id FROM models WHERE project_id = ?)';
			$args[] = $options['project_id'];
		}
		
		$query = $db->prepare($sql.' WHERE '.implode(' AND ',$where));
		assert($query->execute($args),'Was not able to load flows');
		$rows = $query->fetchAll(PDO::FETCH_ASSOC);
		
		$flows = [];
		
		foreach ($rows as $row){
			$flow = new FlowInstance();
			$flow_id = $row['flow_id'];
			if (!isset($bases[$flow_id])) $bases[$flow_id] = Flow::load(['ids'=>$flow_id]);
			$flow->base = $bases[$flow_id];
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
		//debug(['item'=>$this,'method'=>'delete']);
		$db = get_or_create_db();
		$query = $db->prepare('DELETE FROM flow_instances WHERE id = :id');
		$args = [':id'=>$this->id];
		debug(query_insert($query,$args));
		assert($query->execute($args),t('Was not able to remove flow instance "?" from database.',$this->base->id));
		$other_instances = FlowInstance::load(['project_id'=>$this->base->project_id,'flow_id'=>$this->flow_id]);
		if (empty($other_instances)) $this->base->delete();
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
			$known_fields = array_keys(FlowInstance::fields());
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
		
		$sql .= ' ORDER BY name';

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
		if (!isset($this->connectors)) $this->connectors = Connector::load(['model_id'=>$this->id]);
		if ($id) return $this->connectors[$id];
		return $this->connectors;
	}
	
	public function connector_instances($id = null){
		if (!isset($this->connector_instances)) $this->connector_instances = ConnectorInstance::load(['model_id'=>$this->id]);
		if ($id) return $this->connector_instances[$id];
		return $this->connector_instances;
	}

	function delete(){
		foreach ($this->process_instances() as $process) $process->delete();
		foreach ($this->terminal_instances() as $terminal) $terminal->delete();
		$db = get_or_create_db();
		$query = $db->prepare('DELETE FROM models WHERE id = :id');
		$args = [':id'=>$this->id];
		debug(query_insert($query,$args));
		assert($query->execute($args),t('Was not able to remove model "?" from database.',$this->name));
	}

	function findConnectorInstance($cid){
		foreach ($this->processes() as $proc){
			foreach ($proc->connectors as $conn){
				if ($cid == $conn->id) return $conn;
			}
		}
		return null;
	}

	function link($object){
		$db = get_or_create_db();
		if ($object instanceof ProcessInstance) {
			$sql = 'INSERT INTO models_processes (model_id, process_id) VALUES (:mid, :pid)';
			$args = [':mid' => $this->id, ':pid' => $object->id];
			$query = $db->prepare($sql);
			assert($query->execute($args),'Was not able to assign process to model');
			return;
		}
		if ($object instanceof TerminalInstance) {
			$sql = 'INSERT INTO models_terminals (model_id, terminal_id) VALUES (:mid, :tid)';
			$args = [':mid' => $this->id, ':tid' => $object->id];
			$query = $db->prepare($sql);
			assert($query->execute($args),'Was not able to assign terminal to model');
			return;
		}
		warn('Model-&gt;link has no handler for '.$object);
	}

	public function process_instances($id = null){
		if (!isset($this->processes)) $this->processes = ProcessInstance::load(['model_id' => $this->id]);
		//debug($this->processes,1);
		if ($id) return $this->processes[$id];
		return $this->processes;
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

	public function terminal_instances($id = null){
		if (!isset($this->terminals)) $this->terminals = TerminalInstance::load(['model_id' => $this->id]);
		if ($id) return $this->terminals[$id];
		return $this->terminals;
	}

	public function url(){
		return getUrl('model',$this->id.'/view');
	}
}

class Process extends BaseClass{
	/** static functions **/
	static function fields(){
		return [
			'id' => ['VARCHAR'=>255, 'NOT NULL'],
			'project_id' => ['INT','NOT NULL'],
			'description' => 'TEXT',
			'r' => ['INT','DEFAULT 30'],
			'PRIMARY KEY'=>'(id, project_id)',
		];
	}

	static function load($options = []){
		$db = get_or_create_db();

		$where = [];
		$args = [];

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
		
		if (isset($options['project_id'])){
			$where[] = 'project_id = ?';
			$args[] = $options['project_id'];
		}
		
		$sql .= implode(' AND ', $where);

		$query = $db->prepare($sql);
		assert($query->execute($args),'Was not able to load processes');
		$rows = $query->fetchAll(PDO::FETCH_ASSOC);

		$bases = [];
		foreach ($rows as $row){
			$base = new Process($row['id']);
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
		return ProcessInstance::load(['model_id'=>$this->model_id,'parent'=>$this->id]);
	}
	
	public function delete(){
		$db = get_or_create_db();
		$query = $db->prepare('DELETE FROM processes WHERE project_id = :pid AND id = :id');
		$args = [':pid'=>$this->project_id,':id'=>$this->id];
		debug(query_insert($query,$args));
		$query->execute($args);
	}
	
	public function instances($options = []){
		$options['base'] = $this;
		return ProcessInstance::load($options); 
	}
	
	public function save(){
		$db = get_or_create_db();
		if (isset($this->id)){
			if (!empty($this->dirty)){
				$sql = 'UPDATE processes SET';
				$args = [':id'=>$this->id];
								
				foreach ($this->dirty as $field){
					if (array_key_exists($field, Process::fields())){
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
			$known_fields = array_keys(Process::fields());
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
		$process_instances = ProcessInstance::load(['model_id'=>$this->model_id,'process_id'=>$this->id]);
		$connectors = Connector::load(['model_id'=>$this->model_id,'process_id'=>$this->id]);
		$children = ProcessInstance::load(['model_id'=>$this->model_id,'parent'=>$this->id]);
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

class ProcessInstance extends BaseClass{
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
		$where = [];
		$args = [];

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
		
		if (isset($options['model_id'])){
			$where[] = 'model_id = ?';
			$args[] = $options['model_id'];
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
		
		if (isset($options['project_id'])){
			$where[] = 'model_id IN (SELECT id FROM models WHERE project_id = ?)';
			$args[] = $options['project_id'];
		}
		
		$sql = 'SELECT * FROM process_instances WHERE '.implode(' AND ', $where).' ORDER BY process_id';
		$db = get_or_create_db();
		$query = $db->prepare($sql);
		//debug(query_insert($query,$args));
		assert($query->execute($args),'Was not able to load processes');
		$rows = $query->fetchAll(PDO::FETCH_ASSOC);
		
		$models = [];
		$bases = [];

		$processes = [];
		foreach ($rows as $row){
			$process_id = $row['process_id'];
			$model_id = $row['model_id'];
			
			if (!isset($bases[$process_id])){ // load bases on demand
				if (!isset($models[$model_id])) $models[$model_id] = Model::load(['ids'=>$model_id]); // load models on demand, needed to retrieve base 
				$bases[$process_id] = Process::load(['ids'=>$process_id,'project_id' => $models[$model_id]->project_id]);
			}
			
			$process = new ProcessInstance();
			$process->base = $bases[$process_id];
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
			$connector = new ConnectorInstance();
			$connector->base = $base;
			$connector->patch(['model_id'=>$this->model_id,'connector_id'=>$base->id,'process_instance_id'=>$this->id,'angle'=>180*$base->direction]);
			$connector->save();
		}
	}

	function children($id = null){
		if (!isset($this->children)) $this->children = ProcessInstance::load(['model_id'=>$this->model_id,'parent'=>$this->process_id]);
		if ($id) return $this->children[$id];
		return $this->children;
	}

	function connectors($id = null){
		if (!isset($this->connectors)) {
			$base_connectors = Connector::load(['project_id'=>$this->base->project_id,'process_id'=>$this->base->id]);
			if (empty($base_connectors)) {
				$this->connectors = [];
			} else {
				$this->connectors = ConnectorInstance::load(['model_id'=>$this->model_id,'process_instance_id'=>$this->id]);
				foreach ($this->connectors as $conn) unset($base_connectors[$conn->connector_id]);
				if (!empty($base_connectors)) {
					$this->add_connector_instances($base_connectors);
					$this->connectors = ConnectorInstance::load(['model_id'=>$this->model_id,'process_instance'=>$this->id]);
				}
			}
		}
		if ($id) {
			if (array_key_exists($id, $this->connectors)) return $this->connectors[$id];
			return null;
		}
		return $this->connectors;
	}
	
	function delete(){
		//debug(['item'=>$this,'method'=>'delete']);
		foreach ($this->children() as $child_process) $child_process->delete();
		foreach ($this->connectors() as $conn) $conn->delete();
		
		$db = get_or_create_db();
		$query = $db->prepare('DELETE FROM process_instances WHERE id = :id');
		$args = [':id'=>$this->id];
		debug(query_insert($query,$args));
		assert($query->execute($args),t('Was not able to process instance "?" from database.',$this->base->id));
		$other_instances = ProcessInstance::load(['project_id'=>$this->base->project_id,'process_id'=>$this->process_id]);
		if (empty($other_instances)) $this->base->delete();
	}
	
	function parent_process(){
		if ($this->parent) return Process::load(['model_id'=>$this->model_id,'ids'=>$this->parent]);
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
					if (array_key_exists($field, ProcessInstance::fields())){
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
			$known_fields = array_keys(ProcessInstance::fields());
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

	function svg(&$model, &$parent = null, $options = []){
		$factor = isset($options['factor']) ? $options['factor'] : 1.0;
		$arrows = isset($options['arrows']) ? $options['arrows'] : true;

		$referenced_terminal_instances = [];
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
				if ($arrows){
					foreach ($conn->flows() as $flow){
						if ($flow->start_terminal){
							$terminal = $model->terminal_instances($flow->start_terminal)->applyFactor($factor);
							$referenced_terminal_instances[$terminal->id] = $terminal;
							
							$x2 =  sin($conn->angle*RAD)*$this->base->r;
							$y2 = -cos($conn->angle*RAD)*$this->base->r;
							
							$x1 = -$this->x + $terminal->x + $terminal->base->w/2;
							$y1 = -$this->y + $terminal->y + ($terminal->y > $y2 ? 0 : 30) + 25;
	
							$proc_pointer = $parent;
							while ($proc_pointer){
								$x1 -= $proc_pointer->x;
								$y1 -= $proc_pointer->y;
								$proc_pointer = $proc_pointer->parent;
							}
	
							arrow($x1,$y1,$x2,$y2,$flow->base->id,getUrl('model',$model->id.'/flow/'.$flow->id));
							continue;
						}
	
						if ($flow->end_terminal){
							$terminal = $model->terminal_instances($flow->end_terminal)->applyFactor($factor);
								
							$referenced_terminal_instances[$terminal->id] = $terminal;
							
							$x1 = + sin($conn->angle*RAD)*$this->base->r;
							$y1 = - cos($conn->angle*RAD)*$this->base->r;
	
							$x2 = -$this->x + $terminal->x + $terminal->base->w/2;
							$y2 = -$this->y + $terminal->y + ($terminal->y > $y1 ? 0 : 30);
	
							$proc_pointer = $this;
							while (isset($proc_pointer->parent_instance)){
								$proc_pointer = $proc_pointer->parent_instance;
								$x2 -= $proc_pointer->x;
								$y2 -= $proc_pointer->y;
							}
	
							arrow($x1,$y1,$x2,$y2,$flow->base->id,getUrl('model',$model->id.'/flow/'.$flow->id));
							continue;
						}
	
						if ($conn->base->direction){ // OUT
							if ($flow->start_connector != $conn->id) continue;
							if ($parent === null) { // flow goes to connector of top-level process
								foreach ($model->process_instances() as $top_process){
									$end_connector = $top_process->connectors($flow->end_connector);
									if ($end_connector){
										$x1 = $this->base->r*sin($conn->angle*RAD);
										$y1 = -$this->base->r*cos($conn->angle*RAD);
										
										$x2 = -$this->x + $top_process->x + $top_process->base->r * sin($end_connector->angle*RAD);
										$y2 = -$this->y + $top_process->y - $top_process->base->r * cos($end_connector->angle*RAD);
										
										arrow($x1,$y1,$x2,$y2,$flow->base->id,getUrl('model',$model->id.'/flow/'.$flow->id));										
										break;
									}
								}
							} else {
								$end_connector = $parent->connectors($flow->end_connector);
								
								if ($end_connector){ // flow goes to connector of parent
									$x1 = $this->base->r*sin($conn->angle*RAD);
									$y1 = -$this->base->r*cos($conn->angle*RAD);
		
									$x2 = -$this->x + $parent->base->r * sin($end_connector->angle*RAD);
									$y2 = -$this->y - $parent->base->r * cos($end_connector->angle*RAD);
		
									arrow($x1,$y1,$x2,$y2,$flow->base->id,getUrl('model',$model->id.'/flow/'.$flow->id));
								}
							}
						} else { // IN
							if ($flow->end_connector != $conn->id) continue;
							if ($parent === null) continue; 
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
				} // if draw arrows
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
			$options['arrows'] = true; // arrows may have been disabled on upper level. however, draw them in nested structures
			foreach ($this->children() as $child) {
				$child->parent_instance = &$this;
				$terminal_references = $child->svg($model,$this,$options);
				$referenced_terminal_instances = array_merge($referenced_terminal_instances,$terminal_references);
			} ?>
		</g><?php
		return $referenced_terminal_instances; 
	}

	function url(){
		return getUrl('model',$this->model_id.'/process/'.$this->id);
	}
}

class Terminal extends BaseClass{
	const TERMINAL = 0;
	const DATABASE = 1;
	
	/** static functions **/
	static function fields(){
		return [
			'id' => ['VARCHAR'=>255, 'NOT NULL'],
			'project_id' => ['INT','NOT NULL'],
			'type' => 'INT',
			'description' => 'TEXT',
			'w' => ['INT','DEFAULT 50'],
			'PRIMARY KEY' => '(id, project_id)',
		];
	}
	
	static function load($options = []){
		$db = get_or_create_db();
	
		assert(isset($options['project_id']),'No project id passed to Terminal::load()!');
	
		$where = ['project_id = ?'];
		$args = [$options['project_id']];
		
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
			$base = new Terminal();
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
		$this->patch(['w'=>50]);
	}
	
	public function delete(){
		$db = get_or_create_db();
		$query = $db->prepare('DELETE FROM terminals WHERE project_id = :pid AND id = :id');
		$args = [':pid'=>$this->project_id,':id'=>$this->id];
		debug(query_insert($query,$args));
		$query->execute($args);
	}
	
	public function instances($options = []){
		return TerminalInstance::load(['terminal_id'=>$this->id]);
	}
	
	public function save(){
		$db = get_or_create_db();
		
		if (isset($this->id)){
			if (!empty($this->dirty)){
				$sql = 'UPDATE terminals SET';
				$args = [':id'=>$this->id];
	
				foreach ($this->dirty as $field){
					if (array_key_exists($field, Terminal::fields())){
						$sql .= ' '.$field.'=:'.$field.',';
						$args[':'.$field] = $this->{$field};
					} elseif ($field == 'name'){
						$sql .= ' id = :new_id,';
						$args[':new_id'] = $this->name;
						$this->update_references($this->name);
					}
				}				
				if (count($args)>1){
					$sql = rtrim($sql,',').' WHERE id = :id AND project_id = :pid';
					$args[':pid'] = $this->project_id;
					$query = $db->prepare($sql);
					assert($query->execute($args),'Was no able to update terminal in database!');
				}
			}
		} else {
			$known_fields = array_keys(Terminal::fields());
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
	
	public function update_references($new_id){
		foreach ($this->instances() as $terminal){
			$terminal->patch(['terminal_id'=>$new_id]);
			$terminal->save();
		}
	}
}

class TerminalInstance extends BaseClass{

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
		$where = [];
		$args = [];

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

		if (isset($options['model_id'])){
			$where[] = 'model_id = ?';
			$args[] = $options['model_id'];
		}

		if (isset($options['project_id'])){
			$where[] = 'model_id IN (SELECT id FROM models WHERE project_id = ?)';
			$args[] = $options['project_id'];
		}

		if (isset($options['terminal_id'])){
			$where[] = 'terminal_id = ?';
			$args[] = $options['terminal_id'];
		}

		$sql = 'SELECT * FROM terminal_instances';
		if (!empty($where)) $sql .= ' WHERE '.implode(' AND ', $where);
		$db = get_or_create_db();
		$query = $db->prepare($sql);
		assert($query->execute($args),'Was not able to load terminals');
		$rows = $query->fetchAll(PDO::FETCH_ASSOC);

		$models = [];
		$bases = [];

		$terminals = [];
		foreach ($rows as $row){

			$terminal_id = $row['terminal_id'];
			$model_id = $row['model_id'];			

			if (!isset($bases[$terminal_id])){ // load bases on demand
				if (!isset($models[$model_id])) $models[$model_id] = Model::load(['ids'=>$model_id]); // load models on demand, needed to retrieve base
				$bases[$terminal_id] = Terminal::load(['ids'=>$terminal_id,'project_id' => $models[$model_id]->project_id]);
			}

			$terminal = new TerminalInstance();
			$terminal->base = $bases[$terminal_id];
			$terminal->patch($row);
			unset($terminal->dirty);
			if ($single) return $terminal;
			$terminals[$row['id']] = $terminal;
		}
		if ($single) return null;
		return $terminals;
	}

	static function load_bases($options = []){
		$db = get_or_create_db();

		assert(isset($options['model_id']),'No model id passed to ProcessInstance::load()!');

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
				$terminal = new TerminalInstance();
				$terminal->patch($row);
				$terminal->dirty = [];
				$terminal->base = $base;
				$terminals[$terminal->id] = $terminal;
			}
		}
		return $terminals;
	}

	/** instance functions **/
	function applyFactor($factor){
		if (!isset($this->appliedFactor)){
			$this->x *= $factor;
			$this->y *= $factor;
			$this->appliedFactor = $factor;
		}
		return $this;
	}
	
	function delete(){
		$db = get_or_create_db();
		$query = $db->prepare('DELETE FROM terminal_instances WHERE id = :id');
		$args = [':id'=>$this->id];
		debug(query_insert($query,$args));
		assert($query->execute($args),t('Was not able to remove terminal "?" from terminal instances table.',$this->name));

		$query = $db->prepare('DELETE FROM flow_instances WHERE (start_terminal = :tid OR end_terminal = :tid)');
		$args = [':tid'=>$this->id];
		debug(query_insert($query,$args));
		assert($query->execute($args),t('Was not able to remove flows from/to terminal "?" from database.',$this->base->id));

		$other_instances = TerminalInstance::load(['project_id'=>$this->base->project_id,'terminal_id'=>$this->terminal_id]);
		if (empty($other_instances)) $this->base->delete();
	}
	
	function isDB(){
		return $this->base->type == Terminal::DATABASE;
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
		if (isset($this->id)){
			if (!empty($this->dirty)){
				$sql = 'UPDATE terminal_instances SET';
				$args = [':id'=>$this->id];

				foreach ($this->dirty as $field){
					if (array_key_exists($field, TerminalInstance::fields())){
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
			$known_fields = array_keys(TerminalInstance::fields());
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

	public function url(){
		return getUrl('model',$this->model_id.'/terminal/'.$this->id);
	}

}
