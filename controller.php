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
				'name'       => ['VARCHAR'=>255,'NOT NULL'],
				'UNIQUE'     => ['project_id','name']
		];
	}

	static function place_table(){
		return [
				'id'                   => ['INTEGER','NOT NULL','KEY'=>'PRIMARY'],
				'process_connector_id' => ['INT','NOT NULL','REFERENCES process_connectors(id)'],
				'process_place_id'     => ['INT','NOT NULL','REFERENCES process_places(id)'],
				'angle'                => ['INT','NOT NULL','DEFAULT'=>0],
				'UNIQUE'               => ['process_connector_id','process_place_id']
		];
	}
	/* end of table functions */
	static function load($options = []){

		$sql   = 'SELECT id,* FROM connectors';
		$where = [];
		$args  = [];
		$single = false;

		if (!empty($options['name'])) {
			$where[] = 'name = ?';
			$args[]  = $options['name'];
		}

		if (!empty($options['process_id'])){
			$sql = 'SELECT process_connectors.id as pc_id, connectors.id as id, project_id, name, angle FROM connectors LEFT JOIN process_connectors ON connectors.id = process_connectors.connector_id';
			$where[] = 'process_id = ?';
			$args[] = $options['process_id'];
		}

		if (!empty($options['project_id'])) {
			$where[] = 'project_id = ?';
			$args[]  = $options['project_id'];
			if (!empty($options['name'])) $single = true; // if name and project id are set, connector should be unique
		}

		if (!empty($where)) $sql .= ' WHERE ('.implode(') AND (', $where).')';

		$db = get_or_create_db();
		$query = $db->prepare($sql);
		if (!$query->execute($args)) throw new Exception('Was not able to read connectors!');

		$rows = $query->fetchAll(INDEX_FETCH);
		// debug(['options'=>$options,'query'=>query_insert($sql, $args),'rows'=>$rows],1);

		$connectors = [];

		foreach ($rows as $id => $row){
			$connector = new Connector();
			$connector->patch($row);
			unset($connector->dirty);

			if ($single) return $connector;
			$connectors[$id] = $connector;
		}
		if ($single) return null;
		return $connectors;
	}

	static function loadPlaces($process_place_id){
		$sql = 'SELECT * FROM connector_places WHERE process_place_id = :ppid';
		$args = [':ppid'=>$process_place_id];
		$db = get_or_create_db();
		$query = $db->prepare($sql);
		//debug(query_insert($query, $args));
		if (!$query->execute($args)) throw new Exception('Was not able to request connector_places for process_place_id = '.$process_place_id);
		return $query->fetchAll(INDEX_FETCH);
	}

	static function updatePlace($data){
		$db = get_or_create_db();
		$sql = 'SELECT id FROM connector_places WHERE process_connector_id = :pcid AND process_place_id = :ppid';
		$args = [':pcid'=>$data['process_connector_id'],':ppid'=>$data['process_place_id']];
		$query = $db->prepare($sql);
		if (!$query->execute($args)) throw new Exception('Was not able to request id from connector_places!');

		$row = $query->fetch(PDO::FETCH_ASSOC);


		if (empty($row)){
			$sql = 'INSERT INTO connector_places (process_connector_id, process_place_id, angle) VALUES (:pcid, :ppid, :angle)';
			$query = $db->prepare($sql);
		} else {
			$sql = 'UPDATE connector_places SET angle = :angle WHERE id = :id';
			$args = [':id' => $row['id']];
			$query = $db->prepare($sql);
		}
		$args[':angle'] = $data['angle'];
		//debug(query_insert($query, $args));
		if (!$query->execute($args)) throw new Exception('Was not able to write to connector_places!');
	}
	/* end of static functions */

	function save(){
		if (!empty($this->id)) return $this->update();

		$keys = [];
		$args = [];
		foreach ($this->dirty as $field){
			if (array_key_exists($field, Connector::fields())){
				$keys[] = $field;
				$args[':'.$field] = $this->{$field};
			}
		}

		$sql = 'INSERT INTO connectors ('.implode(', ',$keys).') VALUES (:'.implode(', :', $keys).' )';
		$db = get_or_create_db();

		$query = $db->prepare($sql);
		//debug(query_insert($query, $args),1);
		if (!$query->execute($args)) throw new Exception('Was not able to store new connector!');

		$this->patch(['id'=>$db->lastInsertId()]);
		unset($this->dirty);

		return $this;
	}

	function svg(){
	}

	function update(){
		$keys = [];
		$args = [':id'=>$this->id];
		foreach ($this->dirty as $field){
			if ($field == 'id') continue;
			if (array_key_exists($field, Connector::fields())){
				$keys[] = $field.' = :'.$field;
				$args[':'.$field] = $this->{$field};
			}
		}

		$sql = 'UPDATE connectors SET '.implode(', ', $keys).' WHERE id = :id';
		$db = get_or_create_db();

		$query = $db->prepare($sql);
		if (!$query->execute($args)) throw new Exception('Was not able to update connector!');

		unset($this->dirty);

		return $this;
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

	static function updateConnector($data){
		$sql = 'UPDATE process_connectors SET angle = :angle WHERE id = :id';
		$args = [':id'=>$data['process_connector_id'],':angle'=>$data['angle']];
		$db = get_or_create_db();
		$query = $db->prepare($sql);
		//debug(query_insert($query, $args));
		if (!$query->execute($args)) throw new Exception('Was not able to update process connector '.$data['id']);
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
		$db = get_or_create_db();

		if ($child instanceof Connector){
			$sql = 'INSERT INTO process_connectors (connector_id, process_id, angle) VALUES (:conn, :proc, :ang)';
			$args = [':proc'=>$this->id,':conn'=>$child->id,':ang'=>0];
			$type = 'connector';
		} else {
			if ($child instanceof Process) {
				$sql = 'INSERT INTO process_places (context, process_id, x, y) VALUES (:ctx, :prc, :x, :y)';
				$args = [':ctx'=>$this->id,':prc'=>$child->id,':x'=>0,':y'=>0];
				$type = 'process';
			} else if ($child instanceof Terminal){
				$sql = 'INSERT INTO terminal_places (context, terminal_id, x, y) VALUES (:ctx, :trm, :x, :y)';
				$args = [':ctx'=>$this->id,':trm'=>$child->id,':x'=>0,':y'=>0];
				$type = 'terminal';
			} else  throw new Exception('No handler for '.get_class($child).' in Process->add(~)!');

			if ($this->isModel()){
				$args[':x']=500;
				$args[':y']=500;
			}
		}
		$query = $db->prepare($sql);
		if (!$query->execute($args)) throw new Exception('Was not able to add new '.$type.' "'.$child->name.'" to '.$this->name);

		return $this;
	}

	function children(){
		return Process::load(['context'=>$this->id]);
	}

	function connectors($process_place_id = null){
		// process_place_id wird genau dann übergeben, wenn der Prozess als Unterprozess eines anderen Prozesses dargestellt wird.
		// Mit anderen Worten: wenn process_place_id null ist, werden die Verbinder des Prozesses abgerufen, der in der obersten Ebene dargestellt wird.
		$connectors = Connector::load(['process_id'=>$this->id]);
		if (empty($process_place_id)) return $connectors;

		// An dieser Stelle enthält connectors die Verbinder, die dem Prozess zugeordnet sind.
		// Wir haben aber einen Kontext (der Prozess wird als Unterprozess eines anderen Prozesses dargestellt) und wir müssen schauen, ob es für den Kontext gespeicherte Positionen gibt.

		$overrides = Connector::loadPlaces($process_place_id);
		foreach ($overrides as $override){
			$index = $override['process_connector_id'];
			$connectors[$index]->angle = $override['angle'];
		}
		return $connectors;
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

	function show_connectors($process_place_id = null){
		$connectors = $this->connectors($process_place_id);

		foreach ($connectors as $pc_id => $connector) { ?>
			<a xlink:href="<?= getUrl('model') ?>">
				<circle class="connector" cx="0" cy="<?= -$this->r ?>" r="15" id="<?= $pc_id ?>" transform="rotate(<?= $connector->angle ?>,0,0)" <?= !empty($process_place_id)?'place_id="'.$process_place_id.'"':''?>>
					<title><?= $connector->name ."\n\n". t('Mouse wheel alters position.') ?></title>
				</circle>
			</a><?php
		}
	}

	function show_processes(){
		$processes = $this->children();
		foreach ($processes as $proces_place_id => $process) $process->svg($proces_place_id);
	}

	function show_terminals(){
		$terminals = $this->terminals();
		foreach ($terminals as $place_id => $terminal) $terminal->svg($place_id);
	}

	function svg($proces_place_id = null){
		if (empty($this->r)){ // we try to display a model
			// do not show bubble
		} else { // we try to display a process ?>
			<g class="process" transform="translate(<?= empty($proces_place_id)?500:$this->x ?>,<?= empty($proces_place_id)?500:$this->y ?>)">
				<circle class="process" cx="0" cy="0" r="<?= $this->r ?>" id="<?= $this->id ?>" <?= empty($proces_place_id)?'':'place_id="'.$proces_place_id.'"'?>>
					<title><?= $this->description ?><?= "\n".t('Use Shift+Mousewheel to alter size')?></title>
				</circle>
				<text x="0" y="0"><title><?= $this->description ?><?= "\n".t('Use Shift+Mousewheel to alter size')?></title><?= $this->name ?></text><?php
		}

		$this->show_connectors($proces_place_id);
		if (empty($proces_place_id)) {
			$this->show_processes();
			$this->show_terminals();
		}

		if (empty($this->r)){ // we try to display a model
			// do not show bubble
		} else { // we try to display a process ?></g><?php }

	}

	function terminals(){
		return Terminal::load(['context'=>$this->id]);
	}

	function update(){
		$keys = [];
		$args = [':id'=>$this->id];
		foreach ($this->dirty as $field){
			if ($field == 'id') continue;
			if (array_key_exists($field, Process::fields())){
				$keys[] = $field.' = :'.$field;
				$args[':'.$field] = $this->{$field};
			}
		}

		$sql = 'UPDATE processes SET '.implode(', ', $keys).' WHERE id = :id';
		$db = get_or_create_db();

		$query = $db->prepare($sql);
		if (!$query->execute($args)) throw new Exception('Was not able to update process!');

		unset($this->dirty);

		return $this;
	}
}

class Terminal extends UmbrellaObjectWithId{
	const TERMINAL = 0;
	const DATABASE = 1;

	static function fields(){
		return [
				'id'          => ['INTEGER','NOT NULL','KEY'=>'PRIMARY'],
				'project_id'  => ['INT','NOT NULL'],
				'type'        => ['INT','NOT NULL','DEFAULT'=>0],
				'name'        => ['VARCHAR'=>255,'NOT NULL'],
				'description' => ['TEXT'],
				'w'           => ['INT','NOT NULL','DEFAULT'=>200],
				'UNIQUE'      => ['project_id','name']
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
	/* end of table functions */

	static function load($options = []){

		$sql   = 'SELECT id,* FROM terminals';
		$where = [];
		$args  = [];
		$single = false;

		if (!empty($options['context'])) {
			$fields = array_merge(Terminal::fields(),Terminal::place_table());
			unset($fields['id'],$fields['w'],$fields['UNIQUE']);
			$sql = 'SELECT terminal_places.id as place_id, terminals.id as id, '.implode(', ', array_keys($fields)).', terminal_places.w as w FROM terminals LEFT JOIN terminal_places ON terminals.id = terminal_places.terminal_id';
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
		if (!$query->execute($args)) throw new Exception('Was not able to read terminals!');

		$rows = $query->fetchAll(INDEX_FETCH);
		//debug(['options'=>$options,'query'=>query_insert($sql, $args),'rows'=>$rows],1);

		$terminals = [];

		foreach ($rows as $id => $row){
			$terminal = new Terminal();
			$terminal->patch($row);
			unset($terminal->dirty);

			if ($single) return $terminal;
			$terminals[$id] = $terminal;
		}
		if ($single) return null;
		return $terminals;
	}

	static function updatePlace($values){
		$place_id = $values['place_id'];
		unset($values['place_id']);
		$keys = [];
		$args = [':id'=>$place_id];
		foreach ($values as $key => $val){
			if (array_key_exists($key, Terminal::place_table())) {
				$keys[] = $key.' = :'.$key;
				$args[':'.$key] = $val;
			}
		}
		$sql = 'UPDATE terminal_places SET '.implode(', ', $keys).' WHERE id = :id';
		$db = get_or_create_db();
		$query = $db->prepare($sql);
		if (!$query->execute($args)) throw new Exception('Was not able to update terminal placement!');
	}
	/* end of static functions */

	function save(){
		if (!empty($this->id)) return $this->update();

		$keys = [];
		$args = [];
		foreach ($this->dirty as $field){
			if (array_key_exists($field, Terminal::fields())){
				$keys[] = $field;
				$args[':'.$field] = $this->{$field};
			}
		}

		$sql = 'INSERT INTO terminals ('.implode(', ',$keys).') VALUES (:'.implode(', :', $keys).' )';
		$db = get_or_create_db();

		$query = $db->prepare($sql);
		//debug(query_insert($query, $args),1);
		if (!$query->execute($args)) throw new Exception('Was not able to store new terminal!');

		$this->patch(['id'=>$db->lastInsertId()]);
		unset($this->dirty);

		return $this;
	}

	function svg($place_id = null){ ?>
	<g transform="translate(<?= $this->x ?>,<?= $this->y ?>)">
		<?php if ($this->type == Terminal::TERMINAL) { // terminal ?>
		<rect class="terminal" x="0" y="0" width="<?= $this->w ?>" height="30" id="<?= $this->id ?>" <?= empty($place_id)?'':'place_id="'.$place_id.'"'?>>
			<title><?= $this->description ?></title>
		</rect>
		<text x="<?= $this->w/2 ?>" y="15" fill="red"><title><?= $this->description ?></title><?= $this->name ?></text>
		<?php } else { // database ?>
		<ellipse cx="<?= $this->w/2 ?>" cy="40" rx="<?= $this->w/2?>" ry="15">
			<title><?= $this->description ?></title>
		</ellipse>
		<rect class="terminal" x="0" y="0" width="<?= $this->w ?>" height="40" stroke-dasharray="0,<?= $this->w ?>,40,<?= $this->w ?>,40" id="<?= $this->id ?>" <?= empty($place_id)?'':'place_id="'.$place_id.'"'?>>
			<title><?= $this->description ?></title>
		</rect>
		<ellipse cx="<?= $this->w/2 ?>" cy="0" rx="<?= $this->w/2?>" ry="15">
			<title><?= $this->description ?></title>
		</ellipse>
		<text x="<?= $this->w/2 ?>" y="30" fill="red"><title><?= $this->description ?></title><?= $this->name ?></text>
		<?php } ?>
	</g>
	<?php }

	function update(){
		$keys = [];
		$args = [':id'=>$this->id];
		foreach ($this->dirty as $field){
			if ($field == 'id') continue;
			if (array_key_exists($field, Terminal::fields())){
				$keys[] = $field.' = :'.$field;
				$args[':'.$field] = $this->{$field};
			}
		}

		$sql = 'UPDATE terminals SET '.implode(', ', $keys).' WHERE id = :id';
		$db = get_or_create_db();

		$query = $db->prepare($sql);
		if (!$query->execute($args)) throw new Exception('Was not able to update terminal!');

		unset($this->dirty);

		return $this;
	}
}