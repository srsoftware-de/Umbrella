<?php include '../bootstrap.php';

const RAD = 0.01745329;
const MODULE = 'Model';
$title = t('Umbrella Model Management');

function get_or_create_db(){
	if (!file_exists('db')) assert(mkdir('db'),'Failed to create model/db directory!');
	assert(is_writable('db'),'Directory model/db not writable!');
	if (!file_exists('db/models.db')){
		$db = new PDO('sqlite:db/models.db');

		$tables = [
				'processes'          => Process::fields(),
				'terminals'          => Terminal::fields(),
				'connectors'         => Connector::fields(),
				'flows'              => Flow::fields(),
				'process_places'     => Process::place_table(),
				'terminal_places'    => Terminal::place_table(),
				'process_connectors' => Process::connectors_table(),
				'connector_places'   => Connector::place_table(),
				'external_flows'     => Flow::external_table(),
				'internal_flows'     => Flow::internal_table()
		];

		foreach ($tables as $table => $fields){
			$sql = 'CREATE TABLE '.$table.' ( ';
			foreach ($fields as $field => $props) $sql .= field_description($field, $props);
			$sql = str_replace([' ,',', )'],[',',')'],$sql.')');
			assert($db->query($sql),'Was not able to create '.$table.' table in models.db!');
		}
	} else {
		$db = new PDO('sqlite:db/models.db');
	}
	return $db;
}

function arrow($x1,$y1,$x2,$y2,$text = null,$link = null, $title=null){
if ($link){ ?><a xlink:href="<?= $link ?>"><?php } ?>
<g class="arrow">
	<line x1="<?= $x1 ?>" y1="<?= $y1 ?>" x2="<?= $x2 ?>" y2="<?= $y2 ?>" />
	<?php  $dx = $x2 - $x1; $dy = $y2 - $y1; $alpha = ($dy == 0) ? 0 : atan($dx/$dy); if ($dy < 0) $alpha+=pi(); $x1 = $x2 - 25*sin($alpha+0.2); $y1 = $y2 - 25*cos($alpha+0.2); ?>
	<line x1="<?= $x1 ?>" y1="<?= $y1 ?>" x2="<?= $x2 ?>" y2="<?= $y2 ?>" />
	<?php $x1 = $x2 - 25*sin($alpha-0.2); $y1 = $y2 - 25*cos($alpha-0.2); ?>
	<line x1="<?= $x1 ?>" y1="<?= $y1 ?>" x2="<?= $x2 ?>" y2="<?= $y2 ?>" />
	<circle cx="<?= $x2-$dx/2 ?>" cy="<?= $y2-$dy/2 ?>" r="15" />
	<text x="<?= $x2-$dx/2 ?>" y="<?= $y2-$dy/2 ?>"><?= $text ?><?php if (!empty($title)) { ?><title><?= htmlspecialchars($title) ?></title><?php } ?></text>
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


class Connector extends UmbrellaObjectWithId{
	static function fields(){
		return [
				'id'         => ['INTEGER','NOT NULL','KEY'=>'PRIMARY'],
				'project_id' => ['INT','NOT NULL'],
				'name'       => ['VARCHAR'=>255,'NOT NULL']
		];
	}

	static function place_table(){
		return [
				'id'               => ['INTEGER','NOT NULL','KEY'=>'PRIMARY'],
				'connector_id'     => ['INT','NOT NULL','REFERENCES connectors(id)'],
				'process_place_id' => ['INT','NOT NULL','REFERENCES process_places(id)'],
				'angle'            => ['INT','NOT NULL','DEFAULT'=>0],
		];
	}
}

class Flow extends UmbrellaObjectWithId{
	static function fields(){
		return [
				'id'          => ['INTEGER','NOT NULL','KEY'=>'PRIMARY'],
				'project_id'  => ['INT','NOT NULL'],
				'name'        => ['VARCHAR'=>255,'NOT NULL'],
				'definition'  => ['VARCHAR'=>255],
				'description' => ['TEXT']
		];
	}

	static function external_table(){
		return [
				'id'                 => ['INTEGER','NOT NULL','KEY'=>'PRIMARY'],
				'flow_id'            => ['INT','NOT NULL','REFERENCES flows(id)'],
				'ext_id'             => ['INT','NOT NULL'], // references either terminal_places(id) or process_connectors(id)
				'connector_place_id' => ['INT','NOT NULL','REFERENCES connector_places(id)'],
				'type'               => ['INT','NOT NULL'], // either FROM_TERMINAL, TO_TERMINAL, FROM_EXT or TO_EXT
		];
	}

	static function internal_table(){
		return [
				'id'      => ['INTEGER','NOT NULL','KEY'=>'PRIMARY'],
				'flow_id' => ['INT','NOT NULL','REFERENCES flows(id)'],
				'from_id' => ['INT','NOT NULL','REFERENCES connector_places(id)'],
				'to_id'   => ['INT','NOT NULL','REFERENCES connector_places(id)'],
		];
	}
}

class Process extends UmbrellaObjectWithId{
	static function connectors_table(){
		return [
				'id'           => ['INTEGER','NOT NULL','KEY'=>'PRIMARY'],
				'connector_id' => ['INT','NOT NULL','REFERENCES connectors(id)'],
				'process_id'   => ['INT','NOT NULL','REFERENCES processes(id)'],
				'angle'        => ['INT','NOT NULL','DEFAULT'=>0],
		];
	}

	static function fields(){
		return [
				'id'          => ['INTEGER','NOT NULL','KEY'=>'PRIMARY'],
				'project_id'  => ['INT','NOT NULL'],
				'name'        => ['VARCHAR'=>255,'NOT NULL'],
				'description' => ['TEXT'],
				'r'           => ['INT','DEFAULT'=>450],
				'UNIQUE'      => ['project_id','name']
		];
	}

	static function place_table(){
		return [
				'id'         => ['INTEGER','NOT NULL','KEY'=>'PRIMARY'],
				'context'    => ['INT','NOT NULL','REFERENCES processes(id)'],
				'process_id' => ['INT','NOT NULL','REFERENCES processes(id)'],
				'x'          => ['INT','NOT NULL','DEFAULT'=>0], // standardmäßig wird der Kind-Prozess im Zentrum des Eltern-Prozesses angelegt
				'y'          => ['INT','NOT NULL','DEFAULT'=>0], // standardmäßig wird der Kind-Prozess im Zentrum des Eltern-Prozesses angelegt
				'r'          => ['INT','NOT NULL','DEFAULT'=>250],
		];
	}
	/* end of table functions */
	static function load($options = []){

		$sql   = 'SELECT id,* FROM processes';
		$where = [];
		$args  = [];
		$single = false;

		if (!empty($options['context'])) {
			$fields = array_merge(Process::fields(),Process::place_table());
			unset($fields['id'],$fields['UNIQUE'],$fields['r']);
			$sql = 'SELECT process_places.id as place_id, processes.id as id, '.implode(', ', array_keys($fields)).', process_places.r as r FROM processes LEFT JOIN process_places ON processes.id = process_places.process_id';
			$where[] = 'context = ?';
			$args[] = $options['context'];
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

		if (!empty($options['models'])) $where[] = 'r IS NULL';

		if (!empty($options['name'])) {
			$where[] = 'name = ?';
			$args[]  = $options['name'];
		}

		if (!empty($options['project_id'])) {
			$where[] = 'project_id = ?';
			$args[]  = $options['project_id'];
			if (!empty($options['name'])) $single = true; // if name and project id are set, process should be unique
		}

		if (!empty($where)) $sql .= ' WHERE ('.implode(') AND (', $where).')';

		$db = get_or_create_db();
		$query = $db->prepare($sql);
		if (!$query->execute($args)) throw new Exception('Was not able to read processes!');

		$rows = $query->fetchAll(INDEX_FETCH);
		//debug(['options'=>$options,'query'=>query_insert($sql, $args),'rows'=>$rows]);
		$processes = [];

		foreach ($rows as $id => $row){
			$process = new Process();
			$process->patch($row);
			unset($process->dirty);

			if ($single) return $process;
			$processes[$id] = $process;
		}
		if ($single) return null;
		return $processes;
	}

	static function updatePlace($values){
		$place_id = $values['place_id'];
		unset($values['place_id']);
		$keys = [];
		$args = [':id'=>$place_id];
		foreach ($values as $key => $val){
			if (array_key_exists($key, Process::place_table())) {
				$keys[] = $key.' = :'.$key;
				$args[':'.$key] = $val;
			}
		}
		$sql = 'UPDATE process_places SET '.implode(', ', $keys).' WHERE id = :id';
		$db = get_or_create_db();
		$query = $db->prepare($sql);
		if (!$query->execute($args)) throw new Exception('Was not able to update process placement!');
	}

	/* end of static functions */
	function add($child){
		$args = [':ctx'=>$this->id,':prc'=>$child->id,':x'=>0,':y'=>0];
		$sql = 'INSERT INTO process_places (context, process_id, x, y) VALUES (:ctx, :prc, :x, :y)';
		if ($this->isModel()){
			$args[':x']=500;
			$args[':y']=500;
		}
		$db = get_or_create_db();
		$query = $db->prepare($sql);
		if (!$query->execute($args)) throw new Exception('Was not able to add new process '.$child->name.' to '.$this->name);
		return $this;
	}

	function children(){
		return Process::load(['context'=>$this->id]);
	}

	function isModel(){
		return empty($this->r);
	}

	function loadProject(){
		$this->project = request('project','json',['ids'=>$this->project_id]);
		return $this;
	}

	function save(){
		if (!empty($this->id)) return $this->update();

		$keys = [];
		$args = [];
		foreach ($this->dirty as $field){
			if (array_key_exists($field, Process::fields())){
				$keys[] = $field;
				$args[':'.$field] = $this->{$field};
			}
		}

		$sql = 'INSERT INTO processes ('.implode(', ',$keys).') VALUES (:'.implode(', :', $keys).' )';
		$db = get_or_create_db();

		$query = $db->prepare($sql);
		//debug(query_insert($sql, $args),1);
		if (!$query->execute($args)) throw new Exception('Was not able to store new process!');

		$this->patch(['id'=>$db->lastInsertId()]);
		unset($this->dirty);

		return $this;
	}

	function show_processes(){
		$processes = $this->children();
		foreach ($processes as $place_id => $process) $process->svg($place_id);
	}

	function svg($place_id = null){
		if (empty($this->r)){ // we try to display a model
			// do not show bubble
		} else { // we try to display a process ?>
			<g class="process" transform="translate(<?= empty($place_id)?500:$this->x ?>,<?= empty($place_id)?500:$this->y ?>)">
				<circle class="process" cx="0" cy="0" r="<?= $this->r ?>" id="<?= $this->id ?>" <?= empty($place_id)?'':'place_id="'.$place_id.'"'?>>
					<title><?= $this->description ?><?= "\n".t('Use Shift+Mousewheel to alter size')?></title>
				</circle>
				<text x="0" y="0"><title><?= $this->description ?><?= "\n".t('Use Shift+Mousewheel to alter size')?></title><?= $this->name ?></text><?php
		}
		if (empty($place_id)) $this->show_processes();

		if (empty($this->r)){ // we try to display a model
			// do not show bubble
		} else { // we try to display a process ?></g><?php }

	}

	function terminals(){
		error_log('Process->terminals not implemented');
		return [];
	}

	function update(){
		error_log('Process->update not implemented');
		return $this;
	}
}

class Terminal extends UmbrellaObjectWithId{
	static function fields(){
		return [
				'id'          => ['INTEGER','NOT NULL','KEY'=>'PRIMARY'],
				'project_id'  => ['INT','NOT NULL'],
				'type'        => ['INT','NOT NULL','DEFAULT'=>0],
				'name'        => ['VARCHAR'=>255,'NOT NULL'],
				'description' => ['TEXT'],
				'w'           => ['INT','NOT NULL','DEFAULT'=>200]
		];
	}

	static function place_table(){
		return [
				'id'          => ['INTEGER','NOT NULL','KEY'=>'PRIMARY'],
				'context'     => ['INT','NOT NULL','REFERENCES processes(id)'],
				'terminal_id' => ['INT','NOT NULL','REFERENCES terminals(id)'],
				'x'           => ['INT','NOT NULL','DEFAULT'=>500],
				'y'           => ['INT','NOT NULL','DEFAULT'=>500],
				'w'           => ['INT','NOT NULL','DEFAULT'=>200],
		];
	}
}