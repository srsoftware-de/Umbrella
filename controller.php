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
	function __construct(){
		// center process by default
		$this->x = 500;
		$this->y = 500;
	}

	static function fields(){
		return [
			'id'=>['VARCHAR'=>255,'NOT NULL','KEY'=>'PRIMARY'], // composed of project_id:name
			'description'=>['TEXT'],
			'r'=>['INT','NOT NULL','DEFAULT'=>50]
		];
	}

	function add(Process $process){
		$child = new ProcessChild();
		$child->patch(['parent_id'=>$this->id(),'process_id'=>$process->id(),'x'=>$this->r==0?500:0,'y'=>$this->r==0?500:0,'r'=>100]);
		$child->save();
	}

	function children(){
		if (empty($this->children)) {
			$dummy = ProcessChild::load($this->id());
			if (!empty($dummy)){
				$this->children = Process::load(['ids'=>array_keys($dummy)]);
				foreach ($this->children as $id => &$child) $child->patch(['x'=>$dummy[$id]->x,'y'=>$dummy[$id]->y,'r'=>$dummy[$id]->r]);
			}
		}
		return $this->children;
	}

	function terminals(){
		if (empty($this->terminals)){
			$dummy = TerminalInstance::load($this->id());
			if (!empty($dummy)){
				$this->terminals = Terminal::load(['ids'=>array_keys($dummy)]);
				foreach ($this->terminals as $id => &$term) $term->patch(['x'=>$dummy[$id]->x,'y'=>$dummy[$id]->y,'w'=>$dummy[$id]->w]);
			}

		}
		return $this->terminals;
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
		if (empty($this->project['id'])) throw new Exception('Can not save process without project id!');
		if (empty($this->name)) throw new Exception('Can not save project without name');

		$sql = 'REPLACE INTO processes (id, description, r) VALUES (:id, :desc, :r)';
		$args = [':id'=>$this->id(), ':desc'=>$this->description,':r'=>$this->r];
		$db = get_or_create_db();
		$query = $db->prepare($sql);
		if (!$query->execute($args)) throw new Exception('Was not able to execute "'.query_insert($query, $args).'"!');
		unset($this->dirty);
	}

	function svg($show_children = true){
		if ($this->r != 0) { ?>
		<g class="process" transform="translate(<?= $this->x ?>,<?= $this->y ?>)">
			<circle
				class="process"
				cx="0"
				cy="0"
				r="<?= $this->r ?>"
				id="<?= $this->id() ?>">
				<title><?= $this->description."\n".t('Use Shift+Mousewheel to alter size')?></title>
			<?php } // r != 0

			if ($this->r != 0) { ?>
			</circle>
			<text x="0" y="0">
				<title><?= $this->description."\n".t('Use Shift+Mousewheel to alter size')?></title>
				<?= $this->name ?>
			</text><?php } // r != 0
			if ($show_children)	{
				foreach ($this->children() as $child) $child->svg(false);
				foreach ($this->terminals() as $terminal) $terminal->svg(false);
			}
			if ($this->r != 0) { ?></g> <?php } // r != 0
	}

	function terminal_instances(){
		error_log('Process::terminal_instances not implemented');
		return null;
	}
}

class ProcessChild extends UmbrellaObject{
	function __construct(){
		$this->r = 50;
		$this->x = 10;
		$this->y = 10;
	}

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

	static function load($parent_id, $process_id = null){
		$sql  = 'SELECT * FROM process_children WHERE parent_id = ?';
		$args = [$parent_id];

		$single = false;
		if (!empty($process_id)){
			$sql .= ' AND process_id = ?';
			$args[]=$process_id;
			$single = true;
		}

		$db = get_or_create_db();
		$query = $db->prepare($sql);
		//debug(query_insert($query, $args));
		if (!$query->execute($args)) throw new Exception('Was not able to request children of '.$parent_id);
		$rows = $query->fetchAll(PDO::FETCH_ASSOC);
		$children = [];
		foreach ($rows as $row){
			$pc = new ProcessChild();
			$pc->patch($row);
			unset($pc->dirty);
			if ($single) return $pc;
			$children[$pc->process_id] = $pc;
		}
		return $children;
	}

	function save(){
		$sql = 'REPLACE INTO process_children (parent_id, process_id, x, y, r) VALUES (:par, :prc, :x, :y, :r )';
		$args = [':par'=>$this->parent_id,':prc'=>$this->process_id,':x'=>$this->x,':y'=>$this->y,':r'=>$this->r];
		$db = get_or_create_db();
		$query = $db->prepare($sql);
		if (!$query->execute($args)) throw new Exception('Was not able to store process-child assignment!');
		unset($this->dirty);
	}
}

class Terminal extends UmbrellaObjectWithId{
	const TERMINAL = 0;
	const DATABASE = 1;

	static function fields(){
		return [
			'id'=>['VARCHAR'=>255,'NOT NULL','KEY'=>'PRIMARY'], // composed of project_id:name
			'description'=>['TEXT'],
			'type'=>['INT','NOT NULL','DEFAULT'=>1]
		];
	}

	function id(){
		if (empty($this->project['id'])) throw new Exception('Terminal is missing project id!');
		if (empty($this->name)) throw new Exception('Terminal is mission name!');
		return $this->project['id'].':'.$this->name;
	}

	static function load($options = []){
		$sql = 'SELECT * FROM terminals';
		$where = [];
		$args = [];
		$single = false;

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
		$terminals = [];
		foreach ($rows as $id => $row){
			$term = new Terminal();
			$term->patch($row);
			$parts = explode(':', $id,2);
			$proj_id = $parts[0];
			$term->patch(['name'=>$parts[1],'project'=>&$projects[$proj_id]]);
			unset($term->dirty);
			if ($single) return $term;
			$terminals[$id] = $term;
		}
		if ($single) return null;
		return $terminals;
	}

	function save(){
		$sql = 'REPLACE INTO terminals (id, type, description) VALUES (:id, :tp, :ds )';
		$args = [':id'=>$this->id(),':tp'=>$this->type,':ds'=>$this->description];
		debug(query_insert($sql, $args));
		$db = get_or_create_db();

		$query = $db->prepare($sql);
		if (!$query->execute($args)) throw new Exception('Was not able to store process-child assignment!');
		unset($this->dirty);
	}

	public function svg(){ ?>
	<g transform="translate(<?= $this->x ?>,<?= $this->y ?>)">
		<?php if ($this->type == Terminal::TERMINAL) { // terminal ?>
		<rect
				class="terminal"
				x="0"
				y="0"
				width="<?= $this->w ?>"
				height="30"
				id="<?= $this->id() ?>">
			<title><?= $this->description ?></title>
		</rect>
		<text x="<?= $this->w/2 ?>" y="15" fill="red"><title><?= $this->description ?></title><?= $this->id() ?></text>
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
				id="<?= $this->id() ?>">
			<title><?= $this->description ?></title>
		</rect>
		<ellipse
				 cx="<?= $this->w/2 ?>"
				 cy="0"
				 rx="<?= $this->w/2?>"
				 ry="15">
			<title><?= $this->description ?></title>
		</ellipse>
		<text x="<?= $this->w/2 ?>" y="30" fill="red"><title><?= $this->description ?></title><?= $this->id() ?></text>
		<?php } ?>
	</g>
	<?php }
}

class TerminalInstance extends UmbrellaObjectWithId{
	static function fields(){
		return [
			'terminal_id'=>['VARCHAR'=>255,'NOT NULL'],
			'process_id'=>['VARCHAR'=>255,'NOT NULL'],
			'x'=>['INT','NOT NULL','DEFAULT 10'],
			'y'=>['INT','NOT NULL','DEFAULT 10'],
			'w'=>['INT','NOT NULL','DEFAULT 10'],
			'PRIMARY KEY'=>['terminal_id','process_id']
		];
	}

	static function load($process_id, $terminal_id = null){
		$sql  = 'SELECT * FROM terminal_instances WHERE process_id = ?';
		$args = [$process_id];

		$single = false;
		if (!empty($terminal_id)){
			$sql .= ' AND terminal_id = ?';
			$args[]=$terminal_id;
			$single = true;
		}

		$db = get_or_create_db();
		$query = $db->prepare($sql);
		if (!$query->execute($args)) throw new Exception('Was not able to request terminals of '.$process_id);
		$rows = $query->fetchAll(PDO::FETCH_ASSOC);
		$terminals = [];
		foreach ($rows as $row){
			$term = new TerminalInstance();
			$term->patch($row);
			unset($term->dirty);
			if ($single) return $term;
			$terminals[$term->terminal_id] = $term;
		}
		return $terminals;
	}

	function save(){
		$sql = 'REPLACE INTO terminal_instances (process_id, terminal_id, x, y, w) VALUES (:proc, :term, :x, :y, :w )';
		$args = [':proc'=>$this->process_id,':term'=>$this->terminal_id,':x'=>$this->x,':y'=>$this->y,':w'=>$this->w];
		$db = get_or_create_db();
		$query = $db->prepare($sql);
		if (!$query->execute($args)) throw new Exception('Was not able to store process-terminal assignment!');
		unset($this->dirty);
	}
}