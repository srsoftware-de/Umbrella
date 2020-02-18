<?php include '../bootstrap.php';

const RAD = 0.01745329;
const MODULE = 'Model';
const DB_VERSION = 1;
$title = t('Umbrella Model Management');

function db_version(){
	$db = get_or_create_db();
	$query = $db->prepare('SELECT value FROM settings WHERE key = "db_version"');
	if (!$query->execute()) throw new Exception(_('Failed to query db_version!'));
	$rows = $query->fetchAll(PDO::FETCH_COLUMN);
	if (empty($rows)) return null;
	return reset($rows);
}

function get_or_create_db(){
	if (!file_exists('db')) assert(mkdir('db'),'Failed to create model/db directory!');
	assert(is_writable('db'),'Directory model/db not writable!');
	if (!file_exists('db/models.db')){
		$db = new PDO('sqlite:db/models.db');

		$tables = [
				'processes'          => Process::fields(),
				'terminals'          => Terminal::table(),
				'connectors'         => Connector::fields(),
				'fields'			 => Terminal::fields_table(),
				'flows'              => Flow::fields(),
				'process_places'     => Process::place_table(),
				'terminal_places'    => Terminal::place_table(),
				'process_connectors' => Process::connectors_table(),
				'connector_places'   => Connector::place_table(),
				'external_flows'     => Flow::external_table(),
				'internal_flows'     => Flow::internal_table(),

				'diagrams'           => Diagram::fields(),
				'parties'            => Party::fields(),
				'phases'             => Phase::fields(),
				'seps'               => Step::fields(),
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
	static function getOrCreatePlace($process_connector_id,$process_place_id,$default_angle = 0){
		$db = get_or_create_db();
		$sql = 'SELECT * FROM connector_places WHERE process_connector_id = :pcid AND process_place_id = :ppid';
		$args = [':pcid'=>$process_connector_id,':ppid'=>$process_place_id];
		$query = $db->prepare($sql);
		if (!$query->execute($args)) throw new Exception('Was not able to request id from connector_places!');

		$row = $query->fetch(PDO::FETCH_ASSOC);

		if (empty($row)){
			$sql = 'INSERT INTO connector_places (process_connector_id, process_place_id, angle) VALUES (:pcid, :ppid, :angle)';
			$args[':angle'] = $default_angle;
			$query = $db->prepare($sql);
			if (!$query->execute($args)) throw new Exception('Was not able to add new connector_place!');
			$row = [
				'id' => $db->lastInsertId(),
				'process_connector_id' => $process_connector_id,
				'process_place_id' => $process_place_id,
				'angle' => $default_angle
			];
		}
		return $row;
	}

	static function load($options = []){

		$sql   = 'SELECT id,* FROM connectors';
		$where = [];
		$args  = [];
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

		if (!empty($options['key'])){
			$key = '%'.$options['key'].'%';
			$where[] = '(name LIKE ?)';
			$args[] = $key;
		}

		if (!empty($options['name'])) {
			$where[] = 'name = ?';
			$args[]  = $options['name'];
		}

		if (!empty($options['process_id'])){
			$sql = 'SELECT process_connectors.id as pc_id, connectors.id as id, project_id, name, angle FROM connectors LEFT JOIN process_connectors ON connectors.id = process_connectors.connector_id';
			$where[] = 'process_id = ?';
			$args[] = $options['process_id'];
		}

		if (!empty($options['process_connector_id'])){
			$sql = 'SELECT process_connectors.id as pc_id, connectors.id as id, project_id, name, angle FROM connectors LEFT JOIN process_connectors ON connectors.id = process_connectors.connector_id';
			$where[] = 'process_connectors.id = ?';
			$args[] = $options['process_connector_id'];
			$single = true;
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
		//debug(['options'=>$options,'query'=>query_insert($sql, $args),'rows'=>$rows],1);

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

	static function loadPlaces($key_id,$key_is_process_connector_id = false){
		$key = $key_is_process_connector_id ? 'process_connector_id' : 'process_place_id';
		$sql = 'SELECT * FROM connector_places WHERE '.$key.' = :id';
		$args = [':id'=>$key_id];
		$db = get_or_create_db();
		$query = $db->prepare($sql);
		//debug(query_insert($query, $args));
		if (!$query->execute($args)) throw new Exception('Was not able to request connector_places for '.$key.' = '.$key_id);
		return $query->fetchAll(INDEX_FETCH);
	}

	static function removePlaces($args){
		$connector_places = [];
		if (!empty($args['process_place_id']))     $connector_places = Connector::loadPlaces($args['process_place_id'],false);
		if (!empty($args['process_connector_id'])) $connector_places = Connector::loadPlaces($args['process_connector_id'],true);

		$connector_place_ids = array_keys($connector_places);
		if (!empty($connector_place_ids)){
			$qMarks = str_repeat('?,', count($connector_place_ids)-1).'?';
			Flow::removeConnectorPlaces($connector_place_ids);
			$sql = 'DELETE FROM connector_places WHERE id IN ('.$qMarks.')';
			$db = get_or_create_db();
			$query = $db->prepare($sql);
			if (!$query->execute($connector_place_ids)) throw new Exception('Was not able to remove connector places!');
		}
	}

	static function updatePlace($process_connector_id,$process_place_id,$angle){
		$db = get_or_create_db();
		$sql = 'UPDATE connector_places SET angle = :angle WHERE process_connector_id = :pcid AND process_place_id = :ppid';
		$args = [':pcid' => $process_connector_id,':ppid'=>$process_place_id,':angle'=>$angle];
		$query = $db->prepare($sql);
		//debug(query_insert($query, $args));
		if (!$query->execute($args)) throw new Exception('Was not able to write to connector_places!');
	}
	/* end of static functions */

	function delete(){
		$sql = 'SELECT id FROM process_connectors WHERE connector_id = :cid';
		$args=[':cid'=>$this->id];
		$db = get_or_create_db();
		$query = $db->prepare($sql);
		if (! $query->execute($args)) throw new Exception('Was not able to query process connectors');
		$rows = $query->fetchAll(PDO::FETCH_ASSOC);
		foreach ($rows as $row) Connector::removePlaces(['process_connector_id'=>reset($row)]);
		$sql = 'DELETE FROM process_connectors WHERE connector_id = :cid';
		$query = $db->prepare($sql);
		if (! $query->execute($args)) throw new Exception('Was not able to remove connector from processes!');
		$sql = 'DELETE FROM connectors WHERE id = :cid';
		$query = $db->prepare($sql);
		if (! $query->execute($args)) throw new Exception('Was not able to remove connector!');
	}

	function occurences(){
		return Process::load(['connector_id'=>$this->id]);
	}

	function project(){
		if (empty($this->project)) $this->project = request('project','json',['ids'=>$this->project_id]);
		return $this->project;
	}

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
	const FROM_BORDER     = 0;
	const TO_BORDER       = 1;
	const FROM_TERMINAL   = 2;
	const TO_TERMINAL     = 3;
	const EXT_CON_TO_TERM = 4;
	const TERM_TO_EXT_CON = 5;

	static function fields(){
		return [
				'id'          => ['INTEGER','NOT NULL','KEY'=>'PRIMARY'],
				'project_id'  => ['INT','NOT NULL'],
				'name'        => ['VARCHAR'=>255,'NOT NULL'],
				'definition'  => ['VARCHAR'=>255],
				'description' => ['TEXT'],
				'UNIQUE'      => ['project_id','name']
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

	static function add_external($project,$name,$origin,$target,$type){
		$data = ['project_id'=>$project['id'],'name'=>$name];

		// get or create flow:
		$flow = Flow::load($data);
		if (empty($flow)){
			$flow = new Flow();
			$flow->patch($data)->save();
		}

		// get or create connector_place:
		$connector_place = Connector::getOrCreatePlace($target['process_connector_id'], $target['place_id']);

		// create new flow reference:
		$sql = 'INSERT INTO external_flows (flow_id, ext_id, connector_place_id, type) VALUES (:flow_id, :ext_id, :cp_id, :type )';
		$args = [':flow_id'=>$flow->id, ':ext_id' => $origin['process_connector_id'], ':cp_id'=>$connector_place['id'],':type'=>$type];
		$db = get_or_create_db();

		$query = $db->prepare($sql);
		if (!$query->execute($args)) throw new Exception('Was not able to store new external flow!');
		return $flow;
	}

	static function add_internal($project,$name,$origin,$target){
		//debug(['method'=>'Flow::add_internal','name'=>$name,'project'=>$project,'origin'=>$origin,'target'=>$target]);

		$data = ['project_id'=>$project['id'],'name'=>$name];

		// get or create flow:
		$flow = Flow::load($data);
		if (empty($flow)){
			$flow = new Flow();
			$flow->patch($data)->save();
		}

		// get or create connector_places:
		$origin_connector_place = Connector::getOrCreatePlace($origin['process_connector_id'], $origin['place_id']);
		$target_connector_place = Connector::getOrCreatePlace($target['process_connector_id'], $target['place_id']);

		// create new flow reference:
		$sql = 'INSERT INTO internal_flows (flow_id, from_id, to_id) VALUES (:flow_id, :from, :to )';
		$args = [':flow_id'=>$flow->id, ':from' => $origin_connector_place['id'], ':to'=>$target_connector_place['id']];
		$db = get_or_create_db();

		$query = $db->prepare($sql);
		if (!$query->execute($args)) throw new Exception('Was not able to store new internal flow!');
		return $flow;
	}

	static function add_terminal_flow($project,$name,$connector,$terminal,$type){
        //debug(['method'=>'Flow::add_terminal_flow','name'=>$name,'project'=>$project,'conn'=> $connector, 'term' => $terminal, 'type' => $type]);

   		$data = ['project_id'=>$project['id'],'name'=>$name];

		// get or create flow:
		$flow = Flow::load($data);
		if (empty($flow)){
			$flow = new Flow();
			$flow->patch($data)->save();
		}

		$db = get_or_create_db();
		$sql = 'INSERT INTO external_flows (flow_id, ext_id, connector_place_id, type) VALUES (:flow_id, :ext_id, :cp_id, :type )';
		$query = $db->prepare($sql);

		switch ($type){
			case Flow::EXT_CON_TO_TERM:
			case Flow::TERM_TO_EXT_CON:
				$args = [':flow_id'=>$flow->id, ':ext_id' => $terminal['place_id'], ':cp_id'=>$connector['process_connector_id'],':type'=>$type];
				break;
			default:
				$connector_place = Connector::getOrCreatePlace($connector['process_connector_id'], $connector['place_id']);

				// create new flow reference:
				$args = [':flow_id'=>$flow->id, ':ext_id' => $terminal['place_id'], ':cp_id'=>$connector_place['id'],':type'=>$type];
		}
		if (!$query->execute($args)) throw new Exception('Was not able to store new external flow!');
		return $flow;
	}

	static function load($options = []){
		$sql   = 'SELECT id,* FROM flows';
		$where = [];
		$args  = [];
		$single = false;

		if (!empty($options['ext_id'])){
			$sql = 'SELECT flows.id,name,definition,description,project_id,external_flows.id as ext_flow_id, connector_place_id FROM external_flows LEFT JOIN flows ON external_flows.flow_id = flows.id';
			$where = [ 'external_flows.id = ?'];
			$args  = [$options['ext_id']];
			$single=true;
		}

		if (!empty($options['int_id'])){
			$sql = 'SELECT flows.id,name,definition,description,project_id,internal_flows.id as int_flow_id FROM internal_flows LEFT JOIN flows ON internal_flows.flow_id = flows.id';
			$where = [ 'internal_flows.id = ?'];
			$args  = [$options['int_id']];
			$single=true;
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

		if (!empty($options['key'])){
			$key = '%'.$options['key'].'%';
			$where[] = '(name LIKE ? OR description LIKE ? OR definition LIKE ?)';
			$args = array_merge($args, [$key,$key,$key]);
		}

		if (!empty($options['name'])) {
			$where[] = 'name = ?';
			$args[]  = $options['name'];
		}

		if (!empty($options['project_id'])) {
			$where[] = 'project_id = ?';
			$args[]  = $options['project_id'];
			if (!empty($options['name'])) $single = true; // if name and project id are set, flow should be unique
		}

		if (!empty($where)) $sql .= ' WHERE ('.implode(') AND (', $where).')';

		$db = get_or_create_db();
		$query = $db->prepare($sql);

		if (!$query->execute($args)) throw new Exception('Was not able to read flows!');

		$rows = $query->fetchAll($single?PDO::FETCH_ASSOC:INDEX_FETCH);
		//debug(['options'=>$options,'query'=>query_insert($sql, $args),'rows'=>$rows],1);

		$flows = [];

		foreach ($rows as $id => $row){
			$flow = new Flow();
			$flow->patch($row);
			unset($flow->dirty);

			if ($single) return $flow;
			$flows[$id] = $flow;
		}
		if ($single) return null;
		return $flows;
	}

	static function loadExternal($ext_connector,$connector_places,$types){
		$types = implode(', ',$types);
		$args = array_keys($connector_places);
		$qMarks = str_repeat('?,', count($args)-1).'?';
		$args[] = $ext_connector;

		$sql = 'SELECT flows.id as id,project_id,name,description,definition,type,external_flows.id as ext_flow_id,connector_place_id FROM flows LEFT JOIN external_flows ON flows.id = external_flows.flow_id WHERE connector_place_id in ('.$qMarks.') AND ext_id = ? AND type IN ('.$types.')';
		$db = get_or_create_db();
		$query = $db->prepare($sql);
		if (!$query->execute($args)) throw new Exception('Was not able to load flows from external_flows table');
		$rows = $query->fetchAll(INDEX_FETCH);
		$flows = [];
		foreach ($rows as $id => $row){
			$flow = new Flow();
			$flow->patch($row);
			unset($flow->dirty);
			$flows[$id] = $flow;
		}
		return $flows;
	}

    static function loadInternal($connector_place_id_from,$connector_place_id_to){
		$sql = 'SELECT flows.id as id, project_id, name, description, definition, internal_flows.id as int_flow_id FROM internal_flows LEFT JOIN flows ON flows.id = internal_flows.flow_id WHERE from_id = :from AND to_id = :to ';
		$args = [ ':from'=>$connector_place_id_from, ':to'=>$connector_place_id_to];
		$db = get_or_create_db();
		$query = $db->prepare($sql);
		//debug(query_insert($query,$args));
		if (!$query->execute($args)) throw new Exception('Was not able to load flows from internal_flows table');
		$rows = $query->fetchAll(INDEX_FETCH);
		$flows = [];
		foreach ($rows as $id => $row){
			$flow = new Flow();
			$flow->patch($row);
			unset($flow->dirty);
			$flows[$id] = $flow;
		}
		return $flows;
	}

	static function removeConnectorPlaces($connector_place_ids = []){
		if (empty($connector_place_ids)) return;
		$db = get_or_create_db();
		$qMarks = str_repeat('?,', count($connector_place_ids)-1).'?';

		$sql = 'DELETE FROM external_flows WHERE connector_place_id IN ('.$qMarks.')';
		if (!$db->prepare($sql)->execute($connector_place_ids)) throw new Exception('Was not able to remove external flows');

		$sql = 'DELETE FROM internal_flows WHERE from_id IN ('.$qMarks.')';
		if (!$db->prepare($sql)->execute($connector_place_ids)) throw new Exception('Was not able to remove internal flows');

		$sql = 'DELETE FROM internal_flows WHERE to_id IN ('.$qMarks.')';
		if (!$db->prepare($sql)->execute($connector_place_ids)) throw new Exception('Was not able to remove internal flows');
	}

	static function removeExternalFlow($external_flows_id){
		$sql = 'DELETE FROM external_flows WHERE id = :id';
		if (!get_or_create_db()->prepare($sql)->execute([':id'=>$external_flows_id])) throw new Exception('Was not able to remove external flows');
	}

	static function removeInternalFlow($internal_flows_id){
		$sql = 'DELETE FROM internal_flows WHERE id = :id';
		if (!get_or_create_db()->prepare($sql)->execute([':id'=>$internal_flows_id])) throw new Exception('Was not able to remove internal flows');
	}

	static function removeTerminalFlows($terminal_place_ids = []){
		if (empty($terminal_place_ids)) return;
		$qMarks = str_repeat('?,', count($terminal_place_ids)-1).'?';
		$sql = 'DELETE FROM external_flows WHERE ext_id IN ('.$qMarks.') AND type IN ('.Flow::TO_TERMINAL.', '.FLOW::FROM_TERMINAL.')';
		//debug(query_insert($sql, $terminal_place_ids));
		if (!get_or_create_db()->prepare($sql)->execute($terminal_place_ids)) throw new Exception('Was not able to remove external flows');
	}

	/* end of static functions */

	function occurences(){
		if (empty($this->occurences)){
			$db = get_or_create_db();

			$this->occurences = [];
			$args = [':id'=>$this->id];

			$sql = 'SELECT context,process_places.id FROM external_flows LEFT JOIN connector_places ON connector_places.id = external_flows.connector_place_id LEFT JOIN process_places ON process_places.id = process_place_id WHERE flow_id = :id GROUP BY context;';
			$query = $db->prepare($sql);
			if (!$query->execute($args)) throw new Exception('Was not able to read flow metadata');
			$rows = $query->fetchAll(INDEX_FETCH);

			$proc_ids = [];
			if (!empty($rows)) $proc_ids = array_keys($rows);

			$sql = 'SELECT context,process_places.id FROM internal_flows LEFT JOIN connector_places ON connector_places.id = from_id LEFT JOIN process_places ON process_places.id = connector_places.process_place_id WHERE flow_id = :id GROUP BY context;';
			$query = $db->prepare($sql);
			if (!$query->execute($args)) throw new Exception('Was not able to read flow metadata');
			$rows = $query->fetchAll(INDEX_FETCH);
			if (!empty($rows)) $proc_ids = array_merge($proc_ids,array_keys($rows));

			$sql = 'SELECT context,process_places.id FROM internal_flows LEFT JOIN connector_places ON connector_places.id = to_id LEFT JOIN process_places ON process_places.id = connector_places.process_place_id WHERE flow_id = :id GROUP BY context;';
			$query = $db->prepare($sql);
			if (!$query->execute($args)) throw new Exception('Was not able to read flow metadata');
			$rows = $query->fetchAll(INDEX_FETCH);
			if (!empty($rows)) $proc_ids = array_merge($proc_ids,array_keys($rows));

			$this->occurences = empty($proc_ids) ? [] : Process::load(['ids'=>$proc_ids]);
		}
		return $this->occurences;
	}

	function project(){
		if (empty($this->project)) $this->project = request('project','json',['ids'=>$this->project_id]);
		return $this->project;
	}

	function save(){
		if (!empty($this->id)) return $this->update();

		$keys = [];
		$args = [];
		foreach ($this->dirty as $field){
			if (array_key_exists($field, Flow::fields())){
				$keys[] = $field;
				$args[':'.$field] = $this->{$field};
			}
		}

		$sql = 'INSERT INTO flows ('.implode(', ',$keys).') VALUES (:'.implode(', :', $keys).' )';
		$db = get_or_create_db();

		$query = $db->prepare($sql);
		//debug(query_insert($query, $args),1);
		if (!$query->execute($args)) throw new Exception('Was not able to store new flow!');

		$this->patch(['id'=>$db->lastInsertId()]);
		unset($this->dirty);

		return $this;
	}

	function update(){
		$keys = [];
		$args = [':id'=>$this->id];
		foreach ($this->dirty as $field){
			if ($field == 'id') continue;
			if (array_key_exists($field, Flow::fields())){
				$keys[] = $field.' = :'.$field;
				$args[':'.$field] = $this->{$field};
			}
		}

		$sql = 'UPDATE flows SET '.implode(', ', $keys).' WHERE id = :id';
		$db = get_or_create_db();

		$query = $db->prepare($sql);
		if (!$query->execute($args)) throw new Exception('Was not able to update flow!');

		unset($this->dirty);

		return $this;
	}
}

class Diagram extends UmbrellaObjectWithId{
	static function fields(){
		return [
				'id'          => ['INTEGER','NOT NULL','KEY'=>'PRIMARY'],
				'project_id'  => ['INT','NOT NULL'],
				'name'        => ['VARCHAR'=>255,'NOT NULL'],
				'description' => ['TEXT'],
		];
	}

	static function load($options = []){

		$sql   = 'SELECT id,* FROM diagrams';
		$where = [];
		$args  = [];
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

		if (!empty($options['key'])){
			$key = '%'.$options['key'].'%';
			$where[] = '(name LIKE ? OR description LIKE ? OR id IN (SELECT DISTINCT diagram_id FROM parties WHERE name LIKE ? OR description LIKE ?) OR id IN (SELECT DISTINCT diagram_id FROM phases LEFT JOIN steps ON steps.phase_id = phases.id WHERE phases.name LIKE ? OR phases.description LIKE ? OR steps.name LIKE ? OR steps.description LIKE ?))';
			$args = array_merge($args, [$key,$key,$key,$key,$key,$key,$key,$key]);
		}

		if (!empty($options['project_id'])) {
			$where[] = 'project_id = ?';
			$args[]  = $options['project_id'];
			if (!empty($options['name'])) $single = true; // if name and project id are set, process should be unique
		}

		if (!empty($where)) $sql .= ' WHERE ('.implode(') AND (', $where).')';
		//debug(['options'=>$options,'query'=>query_insert($sql, $args)]);
		$db = get_or_create_db();
		$query = $db->prepare($sql);
		if (!$query->execute($args)) throw new Exception('Was not able to read processes!');

		$rows = $query->fetchAll($single?PDO::FETCH_ASSOC:INDEX_FETCH);
		//debug(['options'=>$options,'query'=>query_insert($sql, $args),'rows'=>$rows]);
		$diagrams = [];

		foreach ($rows as $id => $row){
			$diagram = new Diagram();
			$diagram->patch($row);
			unset($diagram->dirty);

			if ($single) return $diagram;
			$diagrams[$id] = $diagram;
		}
		if ($single) return null;
		return $diagrams;
	}

	function parties(){
		if (empty($this->parties)) $this->parties = Party::load(['diagram_id'=>$this->id]);
		return $this->parties;
	}

	function phases(){
		if (empty($this->phases)) $this->phases = Phase::load(['diagram_id'=>$this->id]);
		return $this->phases;
	}

	function project(){
		if (empty($this->project)) $this->project = request('project','json',['ids'=>$this->project_id,'users'=>true]);
		return $this->project;
	}

	function save(){
		if (!empty($this->id)) return $this->update();

		$keys = [];
		$args = [];
		foreach ($this->dirty as $field){
			if (array_key_exists($field, Diagram::fields())){
				$keys[] = $field;
				$args[':'.$field] = $this->{$field};
			}
		}

		$sql = 'INSERT INTO diagrams ('.implode(', ',$keys).') VALUES (:'.implode(', :', $keys).' )';
		$db = get_or_create_db();

		$query = $db->prepare($sql);
		//debug(query_insert($sql, $args),1);
		if (!$query->execute($args)) throw new Exception('Was not able to store new diagram!');

		$this->patch(['id'=>$db->lastInsertId()]);
		unset($this->dirty);

		return $this;
	}

	function update(){
		$keys = [];
		$args = [':id'=>$this->id];
		foreach ($this->dirty as $field){
			if ($field == 'id') continue;
			if (array_key_exists($field, Diagram::fields())){
				$keys[] = $field.' = :'.$field;
				$args[':'.$field] = $this->{$field};
			}
		}

		$sql = 'UPDATE diagrams SET '.implode(', ', $keys).' WHERE id = :id';
		$db = get_or_create_db();

		$query = $db->prepare($sql);
		if (!$query->execute($args)) throw new Exception('Was not able to update diagram!');

		unset($this->dirty);

		return $this;
	}
}

class Party extends UmbrellaObjectWithId{
	static function fields(){
		return [
				'id'          => ['INTEGER','NOT NULL','KEY'=>'PRIMARY'],
				'diagram_id'  => ['INT','NOT NULL','REFERENCES diagrams(id)'],
				'position'    => ['INT','DEFAULT 0'],
				'name'        => ['VARCHAR'=>255,'NOT NULL'],
				'description' => ['TEXT'],
		];
	}

	function diagram(){
		if (empty($this->diagram)) $this->diagram = Diagram::load(['ids'=>$this->diagram_id]);
		return $this->diagram;
	}

	static function load($options = []){

		$sql   = 'SELECT id,* FROM parties';
		$where = [];
		$args  = [];
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

		if (!empty($options['diagram_id'])) {
			$where[] = 'diagram_id = ?';
			$args[]  = $options['diagram_id'];
		}

		if (isset($options['position'])){
			$where[] = 'position = ?';
			$args[] = $options['position'];
			$single = true;
		}

		if (!empty($where)) $sql .= ' WHERE ('.implode(') AND (', $where).')';

		$sql .= ' ORDER BY position';
		//debug(['options'=>$options,'query'=>query_insert($sql, $args)]);
		$db = get_or_create_db();
		$query = $db->prepare($sql);
		if (!$query->execute($args)) throw new Exception('Was not able to read parties!');

		$rows = $query->fetchAll($single?PDO::FETCH_ASSOC:INDEX_FETCH);
		//debug(['options'=>$options,'query'=>query_insert($sql, $args),'rows'=>$rows]);
		$parties = [];

		foreach ($rows as $id => $row){
			$party = new Party();
			$party->patch($row);
			unset($party->dirty);

			if ($single) return $party;
			$parties[$id] = $party;
		}
		if ($single) return null;
		return $parties;
	}

	function save(){
		if (!empty($this->id)) return $this->update();

		$keys = [];
		$args = [];
		foreach ($this->dirty as $field){
			if (array_key_exists($field, Party::fields())){
				$keys[] = $field;
				$args[':'.$field] = $this->{$field};
			}
		}

		$sql = 'INSERT INTO parties ('.implode(', ',$keys).') VALUES (:'.implode(', :', $keys).' )';
		$db = get_or_create_db();

		$query = $db->prepare($sql);
		//debug(query_insert($sql, $args),1);
		if (!$query->execute($args)) throw new Exception('Was not able to store new party!');

		$this->patch(['id'=>$db->lastInsertId()]);
		unset($this->dirty);

		return $this;
	}

	static function shift_positions_from($diagram_id,$position){
		$sql = 'UPDATE parties SET position = position + 1 WHERE diagram_id = :diag AND position >= :pos';
		$args = [':diag'=>$diagram_id,':pos'=>$position];
		$db = get_or_create_db();
		$query = $db->prepare($sql);
		if (!$query->execute($args)) throw new Exception('Was not able to update party positions!');
	}

	function update(){
		$keys = [];
		$args = [':id'=>$this->id];
		foreach ($this->dirty as $field){
			if ($field == 'id') continue;
			if (array_key_exists($field, Party::fields())){
				$keys[] = $field.' = :'.$field;
				$args[':'.$field] = $this->{$field};
			}
		}

		$sql = 'UPDATE parties SET '.implode(', ', $keys).' WHERE id = :id';
		$db = get_or_create_db();

		$query = $db->prepare($sql);
		if (!$query->execute($args)) throw new Exception('Was not able to update party!');

		unset($this->dirty);

		return $this;
	}
}

class Phase extends UmbrellaObjectWithId{
	static function fields(){
		return [
				'id'            => ['INTEGER','NOT NULL','KEY'=>'PRIMARY'],
				'diagram_id'    => ['INT','NOT NULL','REFERENCES diagrams(id)'],
				'position'      => ['INT','DEFAULT 0'],
				'name'          => ['VARCHAR'=>255,'NOT NULL'],
				'description'   => ['TEXT'],
		];
	}

	function diagram(){
		if (empty($this->diagram)) $this->diagram = Diagram::load(['ids'=>$this->diagram_id]);
		return $this->diagram;
	}

	static function load($options = []){

		$sql   = 'SELECT id,* FROM phases';
		$where = [];
		$args  = [];
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

		if (!empty($options['diagram_id'])) {
			$where[] = 'diagram_id = ?';
			$args[]  = $options['diagram_id'];
		}

		if (!empty($where)) $sql .= ' WHERE ('.implode(') AND (', $where).')';
		$sql .= ' ORDER BY position';
		//debug(['options'=>$options,'query'=>query_insert($sql, $args)]);
		$db = get_or_create_db();
		$query = $db->prepare($sql);
		if (!$query->execute($args)) throw new Exception('Was not able to read phases!');

		$rows = $query->fetchAll($single?PDO::FETCH_ASSOC:INDEX_FETCH);
		//debug(['options'=>$options,'query'=>query_insert($sql, $args),'rows'=>$rows]);
		$phases = [];

		foreach ($rows as $id => $row){
			$phase = new Phase();
			$phase->patch($row);
			unset($phase->dirty);

			if ($single) return $phase;
			$phases[$id] = $phase;
		}
		if ($single) return null;
		return $phases;
	}

	function remove(){
		$args = [':id'=>$this->id];
		$db = get_or_create_db();

		$sql = 'DELETE FROM steps WHERE phase_id = :id';
		$query = $db->prepare($sql);
		if (!$query->execute($args)) throw new Exception('Was not able to remove steps of phase!');

		$sql = 'DELETE FROM phases WHERE id = :id';
		$query = $db->prepare($sql);
		if (!$query->execute($args)) throw new Exception('Was not able to remove phase!');

	}

	function save(){
		if (!empty($this->id)) return $this->update();

		$keys = [];
		$args = [];
		foreach ($this->dirty as $field){
			if (array_key_exists($field, Phase::fields())){
				$keys[] = $field;
				$args[':'.$field] = $this->{$field};
			}
		}

		$sql = 'INSERT INTO phases ('.implode(', ',$keys).') VALUES (:'.implode(', :', $keys).' )';
		$db = get_or_create_db();

		$query = $db->prepare($sql);
		//debug(query_insert($sql, $args),1);
		if (!$query->execute($args)) throw new Exception('Was not able to store new phase!');

		$this->patch(['id'=>$db->lastInsertId()]);
		unset($this->dirty);

		return $this;
	}

	static function shift_positions_from($diagram_id,$position){
		$sql = 'UPDATE phases SET position = position + 1 WHERE diagram_id = :diag AND position >= :pos';
		$args = [':diag'=>$diagram_id,':pos'=>$position];
		$db = get_or_create_db();
		$query = $db->prepare($sql);
		if (!$query->execute($args)) throw new Exception('Was not able to update phase positions!');
	}

	function steps(){
		if (empty($this->steps)) $this->steps = Step::load(['phase_id'=>$this->id]);
		return $this->steps;
	}

	function update(){
		$keys = [];
		$args = [':id'=>$this->id];
		foreach ($this->dirty as $field){
			if ($field == 'id') continue;
			if (array_key_exists($field, Phase::fields())){
				$keys[] = $field.' = :'.$field;
				$args[':'.$field] = $this->{$field};
			}
		}

		$sql = 'UPDATE phases SET '.implode(', ', $keys).' WHERE id = :id';
		$db = get_or_create_db();

		$query = $db->prepare($sql);
		if (!$query->execute($args)) throw new Exception('Was not able to update phase!');

		unset($this->dirty);

		return $this;
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
				'r'          => ['INT','NOT NULL','DEFAULT'=>150],
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

		if (!empty($options['process_place_id'])) {
			$fields = array_merge(Process::fields(),Process::place_table());
			unset($fields['id'],$fields['UNIQUE'],$fields['r']);
			$sql = 'SELECT process_places.id as place_id, processes.id as id, '.implode(', ', array_keys($fields)).', process_places.r as r FROM processes LEFT JOIN process_places ON processes.id = process_places.process_id';
			$where[] = 'process_places.id = ?';
			$args[] = $options['process_place_id'];
			$single = true;
		}

		if (!empty($options['connector_id'])){
			$sql = 'SELECT processes.id as id,* FROM process_connectors LEFT JOIN processes ON processes.id = process_connectors.process_id';
			$where= ['process_connectors.id = ?'];
			$args = [$options['connector_id']];
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

		if (!empty($options['key'])){
			$key = '%'.$options['key'].'%';
			$where[] = '(name LIKE ? OR description LIKE ?)';
			$args = array_merge($args, [$key,$key]);
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
		//debug(['options'=>$options,'query'=>query_insert($sql, $args)]);
		$db = get_or_create_db();
		$query = $db->prepare($sql);
		if (!$query->execute($args)) throw new Exception('Was not able to read processes!');

		$rows = $query->fetchAll($single?PDO::FETCH_ASSOC:INDEX_FETCH);
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
				$sql = 'INSERT INTO process_places (context, process_id, r, x, y) VALUES (:ctx, :prc, :r, :x, :y)';
				$args = [':ctx'=>$this->id,':prc'=>$child->id,':x'=>-150,':y'=>-150,':r'=>5*strlen($child->name)];
				$type = 'process';
			} else if ($child instanceof Terminal){
				$sql = 'INSERT INTO terminal_places (context, terminal_id, x, y) VALUES (:ctx, :trm, :x, :y)';
				$args = [':ctx'=>$this->id,':trm'=>$child->id,':x'=>-150,':y'=>-150];
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

		/* connectors ist ein Mapping von process_connectors.ids zu Connectors */

		foreach ($connectors as $process_connector_id => &$connector){
			$override = Connector::getOrCreatePlace($process_connector_id, $process_place_id,$connector->angle);
			$connector->angle = $override['angle'];
		}

		return $connectors;
	}

	static function delete($pid){
		Process::removePlaces($pid);
		Process::unplaceChildren($pid);
		Terminal::removePlaces($pid);
		Process::unplaceConnectors($pid);
		$sql = 'DELETE FROM processes WHERE id = :id';
		$args = [':id'=>$pid];
		if (! get_or_create_db()->prepare($sql)->execute($args)) throw new Exception('Was not able to remove process '.$this->name);
	}

	function isModel(){
		return empty($this->r);
	}

	function occurences(){
		if (empty($this->occurences)){
			$sql = 'SELECT context,process_id FROM process_places LEFT JOIN processes ON context = processes.id WHERE process_id = :id GROUP BY context';
			$args =[':id'=>$this->id];
			$query = get_or_create_db()->prepare($sql);
			if (!$query->execute($args)) throw new Exception('Was not able to request contexts for process!');
			$rows = $query->fetchAll(INDEX_FETCH);
			$this->occurences = empty($rows) ? [] : Process::load(['ids'=>array_keys($rows)]);
		}
		return $this->occurences;
	}

	function project(){
		if (empty($this->project)) $this->project = request('project','json',['ids'=>$this->project_id,'users'=>true]);
		return $this->project;
	}

	static function removePlaces($pid){
		$sql = 'SELECT id FROM process_places WHERE process_id = :pid';
		$args=[':pid'=>$pid];
		$db = get_or_create_db();
		$query = $db->prepare($sql);
		if (! $query->execute($args)) throw new Exception('Was not able to query process places');
		$rows = $query->fetchAll(PDO::FETCH_ASSOC);
		foreach ($rows as $row) Connector::removePlaces(['process_place_id'=>reset($row)]);
		$sql = 'DELETE FROM process_places WHERE process_id = :pid';
		$query = $db->prepare($sql);
		if (! $query->execute($args)) throw new Exception('Was not able to remove process '.$pid.' from contexts!');
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

		foreach ($connectors as $process_connector_id => $connector) {
			$x = sin(RAD*$connector->angle) * $this->r;
			$y = -cos(RAD*$connector->angle) * $this->r;
			?>
			<g class="connector">
				<circle class="connector" cx="0" cy="0" r="15" id="<?= $process_connector_id ?>" transform="translate(<?= $x ?>, <?= $y ?>)" <?= !empty($process_place_id)?'place_id="'.$process_place_id.'"':''?>>
					<title><?= $connector->name ?></title>
				</circle>
			</g><?php
		}

		return $connectors;
	}

	function show_flows($connectors,$child_processes){
		/* connectors is a map from process_connectors.ids to connectors */
		/* child_processes is a map from process_places.ids to processes */

		$url = getUrl('model','flow/');

		/* diplay flows from borders to inner processes */
		foreach ($child_processes as $process_place_id => &$child_process){
			if (empty($child_process->connector_places)) $child_process->connector_places = Connector::loadPlaces($process_place_id);
				foreach ($connectors as $process_connector_id => &$connector){

				$externalFlows = Flow::loadExternal($process_connector_id,$child_process->connector_places,[Flow::FROM_BORDER,Flow::TO_BORDER]);
				foreach ($externalFlows as $flow){
					$start_x = $this->r *  sin(RAD*$connector->angle);
					$start_y = $this->r * -cos(RAD*$connector->angle);
					$end_x = $child_process->x + $child_process->r *  sin(RAD*$child_process->connector_places[$flow->connector_place_id]['angle']);
					$end_y = $child_process->y + $child_process->r * -cos(RAD*$child_process->connector_places[$flow->connector_place_id]['angle']);

					if ($flow->type == Flow::FROM_BORDER){
						arrow($start_x, $start_y, $end_x, $end_y,$flow->name,$url.$flow->ext_flow_id.'?type=ext',htmlspecialchars($flow->description));
					} else {
						arrow($end_x, $end_y,$start_x, $start_y, $flow->name,$url.$flow->ext_flow_id.'?type=ext',htmlspecialchars($flow->description));
					}
				}
			}
		}

		$terminals = Terminal::load(['context'=>$this->id]);
	    //debug(['this'=>$this,'terminals'=>$terminals,'children'=>$child_processes,'connectors'=>$connectors]);

		foreach ($terminals as $terminal_place_id => $terminal){
			// handle flows from terminals to borders of process
			$flows = Flow::loadExternal($terminal_place_id, $connectors,[Flow::EXT_CON_TO_TERM,Flow::TERM_TO_EXT_CON]);
			//debug($flows);
			foreach ($flows as $flow_id => $flow){
				// $flow->connector_place_id ist die id des Connectors
				$x1 = $this->r *   sin(RAD*$connectors[$flow->connector_place_id]->angle);
				$y1 = $this->r *  -cos(RAD*$connectors[$flow->connector_place_id]->angle);

				$x2 = $terminal->x + $terminal->w/2;
				$y2 = $terminal->y;
				if ($y2+70 < $y1) $y2+=($terminal->type ==Terminal::DATABASE ? 55 : 30);

				if ($flow->type == Flow::EXT_CON_TO_TERM){
					arrow($x1, $y1,$x2, $y2, $flow->name,$url.$flow->ext_flow_id.'?type=ext',$flow->description);
				} else {
					arrow($x2, $y2,$x1, $y1, $flow->name,$url.$flow->ext_flow_id.'?type=ext',$flow->description);
				}
			}

			// handle flows from terminals to inner processes
			foreach ($child_processes as $process){
				if (empty($process->connector_places)) continue;
				$flows = Flow::loadExternal($terminal_place_id, $process->connector_places,[Flow::FROM_TERMINAL,Flow::TO_TERMINAL]);
				foreach ($flows as $flow_id => $flow){
					$x1 = $process->x + $process->r *  sin(RAD*$process->connector_places[$flow->connector_place_id]['angle']);
					$y1 = $process->y + $process->r * -cos(RAD*$process->connector_places[$flow->connector_place_id]['angle']);

					$x2 = $terminal->x + $terminal->w/2;
					$y2 = $terminal->y;
					if ($y2+70 < $y1) $y2+=($terminal->type ==Terminal::DATABASE ? 55 : 30);

					if ($flow->type == Flow::FROM_TERMINAL){
						arrow($x2, $y2,$x1, $y1, $flow->name,$url.$flow->ext_flow_id.'?type=ext',$flow->description);
					} else {
						arrow($x1, $y1,$x2, $y2, $flow->name,$url.$flow->ext_flow_id.'?type=ext',$flow->description);
					}
				}
			}
		}

		/* diplay flows between inner processes */
		while (!empty($child_processes)){
            $p1 = array_pop($child_processes);
            foreach ($p1->connector_places as $cp_id_1 => $cp1){
                foreach ($child_processes as $p2){
                    foreach ($p2->connector_places as $cp_id_2 => $cp2){
                        $internal_flows = Flow::loadInternal($cp_id_1,$cp_id_2);
                        foreach ($internal_flows as $flow_id => $flow){
                            $start_x = $p1->x + $p1->r *  sin(RAD*$cp1['angle']);
                            $start_y = $p1->y + $p1->r * -cos(RAD*$cp1['angle']);
                            $end_x   = $p2->x + $p2->r *  sin(RAD*$cp2['angle']);
                            $end_y   = $p2->y + $p2->r * -cos(RAD*$cp2['angle']);
                            arrow($start_x, $start_y, $end_x, $end_y,$flow->name,$url.$flow->int_flow_id.'?type=int',$flow->description);
                        }

                        $internal_flows = Flow::loadInternal($cp_id_2,$cp_id_1);
                        foreach ($internal_flows as $flow_id => $flow){
                            $start_x = $p2->x + $p2->r *  sin(RAD*$cp2['angle']);
                            $start_y = $p2->y + $p2->r * -cos(RAD*$cp2['angle']);
                            $end_x   = $p1->x + $p1->r *  sin(RAD*$cp1['angle']);
                            $end_y   = $p1->y + $p1->r * -cos(RAD*$cp1['angle']);
                            arrow($start_x, $start_y, $end_x, $end_y,$flow->name,$url.$flow->int_flow_id.'?type=int',$flow->description);
                        }
                    }
                }
            }
		}
	}

	function show_processes(){
		$children = $this->children();
		foreach ($children as $proces_place_id => $process) $process->svg($proces_place_id);
		return $children;
	}

	function show_terminals(){
		$terminals = $this->terminals();
		foreach ($terminals as $terminal_place_id => $terminal) $terminal->svg($terminal_place_id);
	}

	function svg($proces_place_id = null){
		if (!empty($this->r)){ // we try to display a process ?>
			<g class="process" transform="translate(<?= empty($proces_place_id)?500:$this->x ?>,<?= empty($proces_place_id)?500:$this->y ?>)">
				<circle class="process" cx="0" cy="0" r="<?= $this->r ?>" id="<?= $this->id ?>" <?= empty($proces_place_id)?'':'place_id="'.$proces_place_id.'"'?>>
					<title><?= htmlspecialchars($this->description) ?><?= "\n\n".t('Use Shift+Mousewheel to alter size.')?></title>
				</circle>
				<text x="0" y="0"><title><?= htmlspecialchars($this->description) ?><?= "\n".t('Use Shift+Mousewheel to alter size')?></title><?= $this->name ?></text><?php
		}

		$connectors = $this->show_connectors($proces_place_id);
		if (empty($proces_place_id)) {
			$child_processes = $this->show_processes();
			$this->show_terminals();
			$this->show_flows($connectors,$child_processes);
		}

		if (!empty($this->r)){ // we try to display a process ?></g><?php }
	}

	function terminals(){
		return Terminal::load(['context'=>$this->id]);
	}

	static function unplaceChildren($pid){
		$sql = 'SELECT id FROM process_places WHERE context = :pid';
		$args=[':pid'=>$pid];
		$db = get_or_create_db();
		$query = $db->prepare($sql);
		if (! $query->execute($args)) throw new Exception('Was not able to query process places');
		$rows = $query->fetchAll(PDO::FETCH_ASSOC);
		foreach ($rows as $row) Connector::removePlaces(['process_place_id'=>reset($row)]);
		$sql = 'DELETE FROM process_places WHERE context = :pid';
		$query = $db->prepare($sql);
		if (! $query->execute($args)) throw new Exception('Was not able to remove process '.$this->name.' from contexts!');
	}

	static function unplaceConnectors($pid){
		$sql = 'SELECT id FROM process_connectors WHERE process_id = :pid';
		$args=[':pid'=>$pid];
		$db = get_or_create_db();
		$query = $db->prepare($sql);
		if (! $query->execute($args)) throw new Exception('Was not able to query process connectors');
		$rows = $query->fetchAll(PDO::FETCH_ASSOC);
		foreach ($rows as $row) Connector::removePlaces(['process_connector_id'=>reset($row)]);
		$sql = 'DELETE FROM process_connectors WHERE process_id = :pid';
		$query = $db->prepare($sql);
		if (! $query->execute($args)) throw new Exception('Was not able to remove process '.$this->name.' from contexts!');
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

class Settings {
	static function table(){
		return [
				'key'	=> ['VARCHAR'=>255,'KEY'=>'PRIMARY'],
				'value'	=> ['VARCHAR'=>255,'NOT NULL'],
		];
	}
}

class Step extends UmbrellaObjectWithId{
	static function fields(){
		return [
				'id'            => ['INTEGER','NOT NULL','KEY'=>'PRIMARY'],
				'phase_id'      => ['INT','NOT NULL','REFERENCES phases(id)'],
				'position'      => ['INT','DEFAULT 0'],
				'name'          => ['VARCHAR'=>255,'NOT NULL'],
				'description'   => ['TEXT'],
				'source'        => ['INT','DEFAULT NULL','REFERENCES parties(id)'],
				'destination'   => ['INT','DEFAULT NULL','REFERENCES parties(id)'],
				'loop'          => ['INT','DEFAULT NULL','REFERENCES steps(id)'],
		];
	}

	static function load($options = []){

		$sql   = 'SELECT id,* FROM steps';
		$where = [];
		$args  = [];
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

		if (!empty($options['phase_id'])) {
			$where[] = 'phase_id = ?';
			$args[]  = $options['phase_id'];
		}
		if (!empty($where)) $sql .= ' WHERE ('.implode(') AND (', $where).')';

		$sql .= ' ORDER BY position';
		$db = get_or_create_db();
		$query = $db->prepare($sql);
		//debug(['options'=>$options,'query'=>query_insert($sql, $args)]);
		if (!$query->execute($args)) throw new Exception('Was not able to read steps!');

		$rows = $query->fetchAll($single?PDO::FETCH_ASSOC:INDEX_FETCH);
		//debug(['options'=>$options,'query'=>query_insert($sql, $args),'rows'=>$rows]);
		$steps = [];

		foreach ($rows as $id => $row){
			$step = new Step();
			$step->patch($row);
			unset($step->dirty);

			if ($single) return $step;
			$steps[$id] = $step;
		}
		if ($single) return null;
		return $steps;
	}

	function moveUp(){
		$db = get_or_create_db();

		$sql = 'UPDATE steps SET position = position +1 WHERE phase_id = :pid AND position = :pos';
		$args = [':pid'=>$this->phase_id,':pos'=>$this->position -1];
		$query = $db->prepare($sql);
		if (!$query->execute($args)) throw new Exception('Was not able top update position!');

		$this->patch(['position'=>$this->position -1])->save();
	}

	function phase(){
		if (empty($this->phase)) $this->phase = Phase::load(['ids'=>$this->phase_id]);
		return $this->phase;
	}

	function save(){
		if (!empty($this->id)) return $this->update();

		$keys = [];
		$args = [];
		foreach ($this->dirty as $field){
			if (array_key_exists($field, Step::fields())){
				$keys[] = $field;
				$args[':'.$field] = $this->{$field};
			}
		}

		$sql = 'INSERT INTO steps ('.implode(', ',$keys).') VALUES (:'.implode(', :', $keys).' )';
		$db = get_or_create_db();

		$query = $db->prepare($sql);
		//debug(query_insert($sql, $args),1);
		if (!$query->execute($args)) throw new Exception('Was not able to store new step!');

		$this->patch(['id'=>$db->lastInsertId()]);
		unset($this->dirty);

		return $this;
	}

	function remove(){
		$sql = 'DELETE FROM steps WHERE id = :id';
		$args = [':id'=>$this->id];
		$db = get_or_create_db();
		$query = $db->prepare($sql);
		if (!$query->execute($args)) throw new Exception('Was not able to remove step!');

		$sql = 'UPDATE steps SET position = position -1 WHERE phase_id = :phase AND position >= :pos';
		$args = [':phase'=>$this->phase()->id,':pos'=>$this->position];
		$query = $db->prepare($sql);
		if (!$query->execute($args)) throw new Exception('Was not able to update step positions!');
	}

	static function shift_positions_from($phase_id,$position){
		$sql = 'UPDATE steps SET position = position + 1 WHERE phase_id = :phase AND position >= :pos';
		$args = [':phase'=>$phase_id,':pos'=>$position];
		$db = get_or_create_db();
		$query = $db->prepare($sql);
		if (!$query->execute($args)) throw new Exception('Was not able to update step positions!');
	}

	function update(){
		$keys = [];
		$args = [':id'=>$this->id];
		foreach ($this->dirty as $field){
			if ($field == 'id') continue;
			if (array_key_exists($field, Step::fields())){
				$keys[] = $field.' = :'.$field;
				$args[':'.$field] = $this->{$field};
			}
		}

		$sql = 'UPDATE steps SET '.implode(', ', $keys).' WHERE id = :id';
		$db = get_or_create_db();

		$query = $db->prepare($sql);
		if (!$query->execute($args)) throw new Exception('Was not able to update step!');

		unset($this->dirty);

		return $this;
	}
}

class Terminal extends UmbrellaObjectWithId{
	const TERMINAL = 0;
	const DATABASE = 1;

	static function table(){
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

	static function fields_table(){
		return [
				'id'          => ['INTEGER','NOT NULL','KEY'=>'PRIMARY'],
				'terminal_id' => ['INT','NOT NULL','REFERENCES terminals(id)'],
				'name'        => ['VARCHAR'=>255,'NOT NULL'],
				'type'        => ['VARCHAR'=>255,'NOT NULL'],
				'not_null'    => ['INT','DEFAULT'=>1],
				'default_val' => ['VARCHAR'=>255],
				'key_type'    => ['CHAR','DEFAULT'=>'NULL'],
				'reference'   => ['INT','REFERENCES fields(id)'],
				'description' => ['TEXT'],
				'UNIQUE'      => ['terminal_id','name']
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

	static function field($id){
		$sql = 'SELECT terminals.id as id, terminals.name as tName, fields.name as fName FROM fields LEFT JOIN terminals ON terminals.id = terminal_id WHERE fields.id = :id';
		$query = get_or_create_db()->prepare($sql);
		if (!$query->execute([':id'=>$id])) throw new Exception('Was not able to request field information!');
		return $query->fetch(PDO::FETCH_ASSOC);

	}

	static function load($options = []){

		$sql   = 'SELECT id,* FROM terminals';
		$where = [];
		$args  = [];
		$single = false;

		if (!empty($options['context'])) {
			$fields = array_merge(Terminal::table(),Terminal::place_table());
			unset($fields['id'],$fields['w'],$fields['UNIQUE']);
			$sql = 'SELECT terminal_places.id as place_id, terminals.id as id, '.implode(', ', array_keys($fields)).', terminal_places.w as w FROM terminals LEFT JOIN terminal_places ON terminals.id = terminal_places.terminal_id';
			$where[] = 'context = ?';
			$args[] = $options['context'];
		}

        if (!empty($options['terminal_place_id'])) {
			$fields = array_merge(Terminal::table(),Terminal::place_table());
			unset($fields['id'],$fields['w'],$fields['UNIQUE']);
			$sql = 'SELECT terminal_places.id as place_id, terminals.id as id, '.implode(', ', array_keys($fields)).', terminal_places.w as w FROM terminals LEFT JOIN terminal_places ON terminals.id = terminal_places.terminal_id';
			$where[] = 'terminal_places.id = ?';
			$args[] = $options['terminal_place_id'];
			$single = true;
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

		if (!empty($options['key'])){
			$key = '%'.$options['key'].'%';
			$where[] = '(name LIKE ? OR description LIKE ? OR id IN (SELECT terminal_id FROM fields WHERE name LIKE ? OR default_val LIKE ? OR type LIKE ? OR description LIKE ?))';
			$args = array_merge($args, [$key,$key,$key,$key,$key,$key]);
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
		//debug(['options'=>$options,'query'=>query_insert($sql, $args),'rows'=>$rows],1);
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

	static function removePlaces($pid){
		$sql = 'SELECT id FROM terminal_places WHERE context = :pid';
		$args=[':pid'=>$pid];
		$db = get_or_create_db();
		$query = $db->prepare($sql);
		if (! $query->execute($args)) throw new Exception('Was not able to query terminal places');
		$rows = $query->fetchAll(PDO::FETCH_ASSOC);
		$terminal_place_ids = [];
		foreach ($rows as $row) $terminal_place_ids[] = reset($row);
		Flow::removeTerminalFlows($terminal_place_ids);
		$sql = 'DELETE FROM terminal_places WHERE context = :pid';
		$query = $db->prepare($sql);
		if (! $query->execute($args)) throw new Exception('Was not able to remove terminals from process '.$pid.'!');
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

	function addField($param){
		$table = Terminal::fields_table();
		unset($table['UNIQUE']);

		$param['terminal_id'] = $this->id;
		$param['not_null'] = (!empty($param['not_null']) && $param['not_null']=='on') ? 1 : 0;
		$param['reference'] = empty($param['reference']) ? 'NULL' : $param['reference'];
		$args=[];
		$keys=[];
		foreach($param as $key => $val){
			if (empty($table[$key])) continue; // $key is not part of the fields_table!
			$args[":$key"] = $val;
			$keys[] = $key;
		}
		if (empty($args)) return;
		$sql = 'INSERT INTO fields ('.implode(', ',$keys).') VALUES (:'.implode(', :',$keys).' );';
		$db = get_or_create_db();
		//debug(query_insert($db->prepare($sql), $args),1);
		if (!$db->prepare($sql)->execute($args)) throw new Exception('Was not able to store new table field!');
	}

	function fields(){
		if (empty($this->fields)){
			$sql = 'SELECT * FROM fields WHERE terminal_id = :tid ORDER BY id';
			$args = [':tid'=>$this->id];
			$query = get_or_create_db()->prepare($sql);
			if (!$query->execute($args)) throw new Exception('Was not able to read "fields" table!');
			$this->fields = $query->fetchAll(INDEX_FETCH);
		}
		return $this->fields;
	}

	function isDB(){
		return $this->type==Terminal::DATABASE;
	}

	function occurences(){
		if (empty($this->occurences)){
			$sql = 'SELECT context,terminal_id FROM terminal_places LEFT JOIN processes ON context = processes.id WHERE terminal_id = :id GROUP BY context';
			$args =[':id'=>$this->id];
			$query = get_or_create_db()->prepare($sql);
			if (!$query->execute($args)) throw new Exception('Was not able to request processes for terminal!');
			$rows = $query->fetchAll(INDEX_FETCH);
			$this->occurences = empty($rows) ? [] : Process::load(['ids'=>array_keys($rows)]);
		}
		return $this->occurences;
	}

	function project(){
		if (empty($this->project)) $this->project = request('project','json',['ids'=>$this->project_id]);
		return $this->project;
	}

	function save(){
		if (!empty($this->new_field['name'])) $this->addField($this->new_field);

		if (!empty($this->id)) return $this->update();

		$keys = [];
		$args = [];
		foreach ($this->dirty as $field){
			if (array_key_exists($field, Terminal::table())){
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

	function svg($terminal_place_id = null){ ?>
	<g transform="translate(<?= $this->x ?>,<?= $this->y ?>)">
		<?php if ($this->type == Terminal::TERMINAL) { // terminal ?>
		<rect class="terminal" x="0" y="0" width="<?= $this->w ?>" height="30" id="<?= $this->id ?>" <?= empty($terminal_place_id)?'':'place_id="'.$terminal_place_id.'"'?>>
			<title><?= htmlspecialchars($this->description) ?></title>
		</rect>
		<text x="<?= $this->w/2 ?>" y="15" fill="red"><title><?= htmlspecialchars($this->description) ?></title><?= $this->name ?></text>
		<?php } else { // database ?>
		<ellipse cx="<?= $this->w/2 ?>" cy="40" rx="<?= $this->w/2?>" ry="15">
			<title><?= htmlspecialchars($this->description) ?></title>
		</ellipse>
		<rect class="terminal" x="0" y="0" width="<?= $this->w ?>" height="40" stroke-dasharray="0,<?= $this->w ?>,40,<?= $this->w ?>,40" id="<?= $this->id ?>" <?= empty($terminal_place_id)?'':'place_id="'.$terminal_place_id.'"'?>>
			<title><?= htmlspecialchars($this->description) ?></title>
		</rect>
		<ellipse cx="<?= $this->w/2 ?>" cy="0" rx="<?= $this->w/2?>" ry="15">
			<title><?= htmlspecialchars($this->description) ?></title>
		</ellipse>
		<text x="<?= $this->w/2 ?>" y="30" fill="red"><title><?= htmlspecialchars($this->description) ?></title><?= $this->name ?></text>
		<?php } ?>
	</g>
	<?php }

	function update(){
		$keys = [];
		$args = [];
		foreach ($this->dirty as $field){
			if ($field == 'id') continue;
			if (array_key_exists($field, Terminal::table())){
				$keys[] = $field.' = :'.$field;
				$args[':'.$field] = $this->{$field};
			}
		}
		if (empty($args)) return $this;
		$args[':id']=$this->id;
		$sql = 'UPDATE terminals SET '.implode(', ', $keys).' WHERE id = :id';
		$db = get_or_create_db();

		$query = $db->prepare($sql);
		if (!$query) throw new Exception(query_insert($sql, $args));
		if (!$query->execute($args)) throw new Exception('Was not able to update terminal ('.query_insert($query, $args).')!');

		unset($this->dirty);

		return $this;
	}
}