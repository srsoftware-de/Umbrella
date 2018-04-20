<?php

const RAD = 0.01745329;

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

function arrow($x1,$y1,$x2,$y2,$text = null,$link = null){
if ($link){ ?><a xlink:href="<?= $link ?>"><?php } ?>
<g class="arrow">
<line x1="<?= $x1 ?>" y1="<?= $y1 ?>" x2="<?= $x2 ?>" y2="<?= $y2 ?>" />
<?php $dx = $x2 - $x1; $dy = $y2 - $y1;	$alpha = ($dy == 0) ? 0 : atan($dx/$dy); $x1 = $x2 - 25*sin($alpha+0.4); $y1 = $y2 - 25*cos($alpha+0.4); ?>
<circle cx="<?= $x2-$dx/2 ?>" cy="<?= $y2-$dy/2 ?>" r="10" />	
<text x="<?= $x2-$dx/2 ?>" y="<?= $y2-$dy/2 ?>"><?= $text ?></text>
<line x1="<?= $x1 ?>" y1="<?= $y1 ?>" x2="<?= $x2 ?>" y2="<?= $y2 ?>" />
<?php $x1 = $x2 - 25*sin($alpha-0.4); $y1 = $y2 - 25*cos($alpha-0.4); ?>
<line x1="<?= $x1 ?>" y1="<?= $y1 ?>" x2="<?= $x2 ?>" y2="<?= $y2 ?>" />
</g>
<?php if ($link){ ?></a><?php } 
}

class Connector{
	const DIR_IN = 0;
	const DIR_OUT = 1;
	
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
	const TO_CONNECTOR = 0;
	const TO_TERMINAL = 1;
	const TO_SIBLING = 2;
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

	static function load($options = []){
		$db = get_or_create_db();
		assert(isset($options['connector']),'No connector id passed to Connector::load()!');

		$sql = 'SELECT * FROM flows
				WHERE ((start_type = '.Flow::TO_CONNECTOR.' AND start_id = ?)
				   OR (end_type = '.Flow::TO_CONNECTOR.' AND end_id = ?))';
		$args = [$options['connector'],$options['connector']];

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

	/** instance methods **/
	function delete(){
		$db = get_or_create_db();
		$query = $db->prepare('DELETE FROM flows WHERE id = :id');
		$args = [':id'=>$this->id];
		debug(query_insert($query, $args));
		assert($query->execute($args),t('Was not able to remove flow "?" from database.',$this->name));
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
	
	public function processes($id = null){
		if (!isset($this->processes)) $this->processes = Process::load(['model_id'=>$this->id]);
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

	public function terminals($id = null){
		if (!isset($this->terminals)) $this->terminals = Terminal::load(['model_id'=>$this->id]);
		if ($id) return $this->terminals[$id];
		return $this->terminals;
	}

	public function url(){
		return getUrl('model',$this->id.'/view');
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

		assert(isset($options['model_id']) || isset($options['parent']) || isset($options['ids']),'Neither ids, model id nor parent process id passed to Process::load()!');

		$where = [];
		$args = [];

		$sql = 'SELECT * FROM processes';
		if (isset($options['model_id'])){
			$sql .= ' LEFT JOIN models_processes ON models_processes.process_id = processes.id';
			$where[] = 'model_id = ?';
			$args[] = $options['model_id'];
		}
		if (isset($options['parent'])){
			$sql .= ' LEFT JOIN child_processes ON child_processes.process_id = processes.id';
			$where[] = 'parent_process = ?';
			$args[] = $options['parent'];
		}

		$single = false;
		if (isset($options['ids'])){
			$ids = $options['ids'];
			if (!is_array($ids)) {
				$single = true;
				$ids = [$ids];
			}

			$qMarks = str_repeat('?,', count($ids)-1).'?';
			$sql .= ' LEFT JOIN child_processes ON child_processes.process_id = processes.id';
			$where[] .= 'id IN ('.$qMarks.')';
			$args = array_merge($args, $ids);
		}
		if (!empty($where)) $sql .= ' WHERE '.implode(' AND ', $where);

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
	function addChild($child_process){
		$db = get_or_create_db();
		$sql = 'INSERT INTO child_processes (process_id, parent_process) VALUES (:child_id, :parent_id)';
		$args = [':child_id' => $child_process->id, ':parent_id' => $this->id];
		$query = $db->prepare($sql);
		assert($query->execute($args),'Was not able to assign child to process');
	}

	function children($id = null){
		if (!isset($this->children)) $this->children = Process::load(['parent'=>$this->id]);
		if ($id) return $this->children[$id];
		return $this->children;
	}

	function connectors($id = null){
		if (!isset($this->connectors)) $this->connectors = Connector::load(['process_id'=>$this->id]);
		if ($id) {
			if (array_key_exists($id, $this->connectors)) return $this->connectors[$id];
			return null;
		}
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
				if (isset($this->parent_process)) {
					$link_sql = 'UPDATE child_processes SET';
					$link_args = [':pid'=>$this->id,':parent'=>$this->parent_process];
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
					$link_sql = rtrim($link_sql,',').' WHERE process_id = :pid';
					if (isset($this->model_id)) $link_sql .= ' AND model_id = :mid';
					if (isset($this->parent_process)) $link_sql .= ' AND parent_process = :parent';
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

	function svg(&$model){
		if (isset($this->parent)){
			$rad = $this->parent->r;
			$this->x = $this->x < -$rad ? -$rad : ($this->x > $rad ? $rad : $this->x);
			$this->y = $this->y < -$rad ? -$rad : ($this->y > $rad ? $rad : $this->y);
			$this->path = $this->parent->path . '.' . $this->id;
		} else {
			$this->x = $this->x < 0 ? 0 : ($this->x > 1000 ? 1000 : $this->x);
			$this->y = $this->y < 0 ? 0 : ($this->y > 1000 ? 1000 : $this->y);
			$this->path = $this->id;
		}
		?>
		<g transform="translate(<?= $this->x ?>,<?= $this->y ?>)">
			<circle
					class="process"
					cx="0"
					cy="0"
					r="<?= $this->r?>"
					id="process_<?= $this->path ?>">
				<title><?= $this->description ?></title>
			</circle>
			<text x="0" y="0" fill="red"><?= $this->name ?></text>
			<?php foreach ($this->connectors() as $conn){

				foreach ($conn->flows() as $flow){

					if ($flow->start_type == Flow::TO_TERMINAL){
						$terminal = $model->terminals($flow->start_id);
						$x2 =  sin($conn->angle*RAD)*$this->r;
						$y2 = -cos($conn->angle*RAD)*$this->r;
					
						$x1 = -$this->x + $terminal->x + $terminal->w/2;
						$y1 = -$this->y + $terminal->y + ($terminal->y > $y2 ? 0 : 30);
					
						$parent = $this->parent;
						while ($parent){
							$x1 -= $parent->x;
							$y1 -= $parent->y;
							$parent = $parent->parent;
						}
						
						arrow($x1,$y1,$x2,$y2,$flow->name,getUrl('model',$model->id.'/flow/'.$this->path.'.'.$conn->id.'.'.$flow->id));
						continue;
					}
						
					if ($flow->end_type == Flow::TO_TERMINAL){
						$terminal = $model->terminals($flow->end_id);
						
						$x1 = + sin($conn->angle*RAD)*$this->r;
						$y1 = - cos($conn->angle*RAD)*$this->r;
						
						$x2 = -$this->x + $terminal->x + $terminal->w/2;
						$y2 = -$this->y + $terminal->y + ($terminal->y > $y1 ? 0 : 30);
						
						$parent = isset($this->parent) ? $this->parent: false;
						while ($parent){
							$x2 -= $parent->x;
							$y2 -= $parent->y;
							$parent = $parent->parent;
						}

						arrow($x1,$y1,$x2,$y2,$flow->name,getUrl('model',$model->id.'/flow/'.$this->path.'.'.$conn->id.'.'.$flow->id));
						continue;
					}
					
					if ($conn->direction){ // OUT
						if ($flow->start_id != $conn->id) continue;
						$c2 = $this->parent->connectors($flow->end_id);
						if ($c2){ // flow goes to connector of parent
							$x1 = $this->r*sin($conn->angle*RAD);
							$y1 = -$this->r*cos($conn->angle*RAD);

							$x2 = -$this->x + $this->parent->r * sin($c2->angle*RAD);
							$y2 = -$this->y - $this->parent->r * cos($c2->angle*RAD);

							arrow($x1,$y1,$x2,$y2,$flow->name,getUrl('model',$model->id.'/flow/'.$this->path.'.'.$conn->id.'.'.$flow->id));
						}
					} else { // IN
						if ($flow->end_id != $conn->id) continue;
						$c2 = $this->parent->connectors($flow->start_id);
						if ($c2 === null){
							foreach ($this->parent->children() as $sibling){
								$c2 = $sibling->connectors($flow->start_id);
								if ($c2) break;
							}
							$x1 = -$this->x + $sibling->x + $sibling->r * sin($c2->angle*RAD);
							$y1 = -$this->y + $sibling->y - $sibling->r * cos($c2->angle*RAD);

							$x2 = $this->r*sin($conn->angle*RAD);
							$y2 = -$this->r*cos($conn->angle*RAD);

							arrow($x1,$y1,$x2,$y2,$flow->name,getUrl('model',$model->id.'/flow/'.$this->path.'.'.$conn->id.'.'.$flow->id));
						} else { // flow comes from connector of parent
							$x1 = -$this->x + $this->parent->r * sin($c2->angle*RAD);
							$y1 = -$this->y - $this->parent->r * cos($c2->angle*RAD);;

							$x2 = $this->r*sin($conn->angle*RAD);
							$y2 = -$this->r*cos($conn->angle*RAD);

							arrow($x1,$y1,$x2,$y2,$flow->name,getUrl('model',$model->id.'/flow/'.$this->path.'.'.$conn->id.'.'.$flow->id));
						}
					}
				}
			?>
			<a xlink:href="connect_<?= $conn->direction == Connector::DIR_IN ? 'in':'out' ?>/<?= $this->path ?>.<?= $conn->id ?>">
				<circle
						class="connector"
						cx="0"
						cy="<?= -$this->r ?>"
						r="15"
						id="connector_<?= $this->path ?>.<?= $conn->id ?>"
						transform="rotate(<?= $conn->angle ?>,0,0)">
					<title><?= $conn->name ?></title>
				</circle>
			</a>
			<?php } // foreach connector
			foreach ($this->children() as $child){
				$child->parent = &$this;
				$child->svg($model,$this);
			}
			?>
		</g>
	<?php }
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

		$single = false;
/*		if (isset($options['ids'])){
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

	public function svg(){ ?>
	<g transform="translate(<?= $this->x>0?$this->x:0 ?>,<?= $this->y>0?$this->y:0 ?>)">
		<?php if (!$this->type) { ?>
		<rect
				class="terminal"
				x="0"
				y="0"
				width="<?= $this->w ?>"
				height="30"
				id="terminal_<?= $this->id ?>">
			<title><?= $this->description ?></title>
		</rect>
		<text x="<?= $this->w/2 ?>" y="15" fill="red"><?= $this->name ?></text>
		<?php } else { ?>
		<ellipse
				 cx="<?= $this->w/2 ?>"
				 cy="40"
				 rx="<?= $this->w/2?>"
				 ry="15">
			<title><?= $this->description ?></title>
		</ellipse>
		<rect
				class="terminal"
				x="0"
				y="0"
				width="<?= $this->w ?>"
				height="40"
			  	stroke-dasharray="0,<?= $this->w ?>,40,<?= $this->w ?>,40"
				id="terminal_<?= $this->id ?>">
			<title><?= $this->description ?></title>
		</rect>
		<ellipse
				 cx="<?= $this->w/2 ?>"
				 cy="0"
				 rx="<?= $this->w/2?>"
				 ry="15">
			<title><?= $this->description ?></title>
		</ellipse>
		<text x="<?= $this->w/2 ?>" y="30" fill="red"><?= $this->name ?></text>
		<?php } ?>
	</g>
	<?php }
}
