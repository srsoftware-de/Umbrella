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
			'connections'=> Connection::fields(),
			'connectors'=> Connector::fields(),
			'terminals'=>Terminal::fields(),
			'terminal_instances'=>TerminalInstance::fields(),
			'flows'=>    Flow::fields(),
			'processes'=>Process::fields(),
			'process_children'=>ProcessChild::fields()
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

class Connection extends UmbrellaObjectWithId{
	static function fields(){
		return [
			'id'=>['INTEGER','NOT NULL','KEY'=>'PRIMARY'],
			'start_connector'=>['VARCHAR'=>255],
			'start_terminal'=>['VARCHAR'=>255],
			'end_connector'=>['VARCHAR'=>255],
			'end_terminal'=>['VARCHAR'=>255],
			'flow_id'=>['VARCHAR'=>255,'NOT NULL'],
		];
	}
}

class Connector extends UmbrellaObjectWithId{
	static function fields(){
		return [
			'id'=>['VARCHAR'=>255,'NOT NULL','KEY'=>'PRIMARY'], // composed of project_id:name
			'process_id'=>['VARCHAR'=>255,'NOT NULL'],
			'angle'=>['INT','NOT NULL','DEFAULT'=>0]
		];
	}
}

class Flow extends UmbrellaObjectWithId{
	static function fields(){
		return [
				'id'=>['VARCHAR'=>255,'NOT NULL','KEY'=>'PRIMARY'], // composed of project_id:name
				'description'=>['TEXT'],
				'definition'=>['TEXT']
		];
	}
}

class Process extends UmbrellaObjectWithId{
	static function fields(){
		return [
				'id'=>['VARCHAR'=>255,'NOT NULL','KEY'=>'PRIMARY'], // composed of project_id:name
				'description'=>['TEXT'],
				'r'=>['INT','NOT NULL','DEFAULT'=>50]
		];
	}

	function add(Process $process){
		$child = new ProcessChild();
		$child->patch(['parent_id'=>$this->id(),'process_id'=>$process->id(),'x'=>10,'y'=>10,'r'=>20]);
		$child->save();
	}

	function children(){
		if (empty($this->children)) $this->children = ProcessChild::load($this->id());
		return $this->children;
	}

	function id(){
		if (empty($this->project['id'])) throw new Exception('Process is missing project id!');
		if (empty($this->name)) throw new Exception('Process is mission name!');
		return $this->project['id'].':'.$this->name;
	}

	function load($options = []){
		$sql = 'SELECT * FROM processes';
		$where = [];
		$args = [];
		$single = false;

		if (isset($options['r'])){
			$where[] = 'r = ?';
			$args[] = $options['r'];
		}

		if (!empty($options['project_id'])){
			if (empty($options['name'])){
				$where[] = 'id LIKE ?';
				$args[] = $options['project_id'].':%';
			} else $options['ids']  = $options['project_id'].':'.$options['name'];
		}

		if (!empty($options['ids'])){
			$ids = $options['ids'];
			if (!is_array($ids)) {
				$ids = [ $ids ];
				$single = true;
			}
			$qMarks = str_repeat('?,', count($ids)-1).'?';
			$where[] = 'id IN ('.$qMarks.')';
			$args = array_merge($args, $ids);
		}

		if (!empty($where)) $sql .= ' WHERE ('.implode(') AND (', $where).')';
		$db = get_or_create_db();
		$query = $db->prepare($sql);
		//debug(query_insert($query, $args));

		if (!$query->execute($args)) throw new Exception('Was not able to execute "'.query_insert($query, $args).'"!');

		$projects = request('project','json');
		$rows = $query->fetchAll(INDEX_FETCH);
		$processes = [];
		foreach ($rows as $id => $row){
			$process = new Process();
			$process->patch($row);
			$parts = explode(':', $id,2);
			$proj_id = $parts[0];
			$process->patch(['name'=>$parts[1],'project'=>&$projects[$proj_id]]);
			unset($process->dirty);
			if ($single) return $process;
			$processes[$id] = $process;
		}
		if ($single) return null;
		return $processes;
	}

	function save(){
		if (empty($this->project_id)) throw new Exception('Can not save process without project id!');
		if (empty($this->name)) throw new Exception('Can not save project without name');

		$sql = 'REPLACE INTO processes (id, description, r) VALUES (:id, :desc, :r)';
		$args = [':id'=>$this->id(), ':desc'=>$this->description,':r'=>$this->r];
		$db = get_or_create_db();
		$query = $db->prepare($sql);
		if (!$query->execute($args)) throw new Exception('Was not able to execute "'.query_insert($query, $args).'"!');
		unset($this->dirty);
	}

	function svg(){
		if ($this->r != 0) { ?>
		<circle
			class="process"
			cx="0"
			cy="0"
			r="<?= $this->r ?>"
			id="<?= $this->id() ?>">
			<title><?= $this->description ?><?= "\n".t('Use Shift+Mousewheel to alter size')?></title>
		<?php } // r != 0
		foreach ($this->children() as $child){ ?>
			<circle
				class="process"
				cx="<?= $child->x ?>"
				cy="<?= $child->y ?>"
				r="<?= $child->r ?>"
				id="<?= $child->id() ?>">
			<title><?= $child->description ?><?= "\n".t('Use Shift+Mousewheel to alter size')?></title>
			</circle> <?php
		}
		if ($this->r != 0) { ?></circle> <?php } // r != 0
	}

	function terminal_instances(){
		error_log('Process::terminal_instances not implemented');
		return null;
	}
}

class ProcessChild extends UmbrellaObject{
	static function fields(){
		return [
			'parent_id'=>['VARCHAR'=>255,'NOT NULL'],
			'process_id'=>['VARCHAR'=>255,'NOT NULL'],
			'x'=>['INT','NOT NULL','DEFAULT 10'],
			'y'=>['INT','NOT NULL','DEFAULT 10'],
			'r'=>['INT','NOT NULL','DEFAULT 10'],
			'PRIMARY KEY'=>['parent_id','process_id']
		];
	}

	static function load($process_id){
		$sql  = 'SELECT * FROM process_children WHERE parent_id = ?';
		$args = [$process_id];
		$db = get_or_create_db();
		$query = $db->prepare($sql);
		if (!$query->execute($args)) throw new Exception('Was not able to request children of '.$process_id);
		$rows = $query->fetchAll(PDO::FETCH_ASSOC);
		$children = [];
		foreach ($rows as $row){
			$pid = $row['process_id'];
			$child = Process::load(['ids'=>$pid]);
			unset($row['parent_id'],$row['process_id']);
			$child->patch($row);
			$children[$pid] = $child;
			unset($child->dirty);
		}
		return $children;
	}

	function save(){
		$sql = 'REPLACE INTO process_children (parent_id, process_id, x, y, r) VALUES (:par, :prc, :x, :y, :r)';
		$args = [':par'=>$this->parent_id,':prc'=>$this->process_id,':x'=>$this->x,':y'=>$this->y,':r'=>$this->r];
		$db = get_or_create_db();
		$query = $db->prepare($sql);
		if (!$query->execute($args)) throw new Exception('Was not able to store process-child assignment!');

	}
}

class Terminal extends UmbrellaObjectWithId{
	static function fields(){
		return [
			'id'=>['VARCHAR'=>255,'NOT NULL','KEY'=>'PRIMARY'], // composed of project_id:name
			'description'=>['TEXT'],
			'type'=>['INT','NOT NULL','DEFAULT'=>1]
		];
	}
}

class TerminalInstance extends UmbrellaObjectWithId{
	static function fields(){
		return [
			'terminal_id'=>['VARCHAR'=>255,'NOT NULL'],
			'process_id'=>['VARCHAR'=>255,'NOT NULL'],
			'x'=>['INT','NOT NULL','DEFAULT 10'],
			'y'=>['INT','NOT NULL','DEFAULT 10'],
			'r'=>['INT','NOT NULL','DEFAULT 10'],
			'PRIMARY_KEY'=>['terminal_id','process_id']
		];
	}
}