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
				'r'           => ['INT','DEFAULT NULL'],
				'UNIQUE'      => ['project_id','name']
		];
	}

	static function place_table(){
		return [
				'id'         => ['INTEGER','NOT NULL','KEY'=>'PRIMARY'],
				'context'    => ['INT','NOT NULL','REFERENCES processes(id)'],
				'process_id' => ['INT','NOT NULL','REFERENCES processes(id)'],
				'x'          => ['INT','NOT NULL','DEFAULT'=>500],
				'y'          => ['INT','NOT NULL','DEFAULT'=>500],
				'r'          => ['INT','NOT NULL','DEFAULT'=>250],
		];
	}
	/* end of table functions */
	static function load($options = []){
		$sql   = 'SELECT id,* FROM processes';
		$where = [];
		$args  = [];
		$single = false;

		if (!empty($options['models'])) $where[] = 'r IS NULL';

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

		$db = get_or_create_db();
		$query = $db->prepare($sql);
		//debug(query_insert($query, $args));

		if (!$query->execute($args)) throw new Exception('Was not able to read processes!');

		$rows = $query->fetchAll(INDEX_FETCH);
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
	/* end of static functions */
	function children(){
		error_log('Process->children not implemented');
		return [];
	}

	function loadProject(){
		$this->project = request('project','json',['ids'=>$this->project_id]);
		return $this;
	}

	function save(){
		if (!empty($this->id)) return $this->update();

		$keys = [];
		$vals = [];
		foreach ($this->dirty as $field){
			if (array_key_exists($field, Process::fields())){
				$keys[] = $field;
				$vals[':'.$field] = $this->{$field};
			}
		}

		$sql = 'INSERT INTO processes ('.implode(', ',$keys).') VALUES (:'.implode(', :', $keys).' )';
		$db = get_or_create_db();
		$query = $db->prepare($sql);

		if (!$query->execute($vals)) throw new Exception('Was not able to store new process!');

		$this->patch(['id'=>$db->lastInsertId()]);
		unset($this->dirty);
		return $this;
	}

	function svg(){
		error_log('Process->svg(context) not implemented');
	}

	function terminals(){
		error_log('Process->terminals not implemented');
		return [];
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