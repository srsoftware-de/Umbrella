<?php include '../bootstrap.php';

const MODULE = 'Mindmaps';
const READ_PERMISSION = 1;
const WRITE_PERMISSION = 2;
$title = t('Umbrella Item Management');

function get_or_create_db(){
	if (!file_exists('db') && !mkdir('db')) throw new Exception('Failed to create '.MODULE.'/db directory!');
	if (!is_writable('db')) throw new Exception('Directory '.MODULE.'/db not writable!');
	if (!file_exists('db/'.MODULE.'.db')){
		$db = new PDO('sqlite:db/'.MODULE.'.db');

		$tables = [
			'mindmaps'=>Mindmap::table(),
			'users'=>User::table()
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
								$sql.= 'DEFAULT '.($prop_v === null?'NULL ':'"'.$prop_v.'" '); break;
							case $prop_k==='KEY':
								if ($prop_v != 'PRIMARY') throw new Exception('Non-primary keys not implemented in '.MODULE.'/controller.php!');
								$sql.= 'PRIMARY KEY '; break;
							default:
								$sql .= $prop_v.' ';
						}
					}
					$sql .= ", ";
				} else $sql .= $props.", ";
			}
			$sql = str_replace([' ,',', )'],[',',')'],$sql.')');
			if (!$db->query($sql)) throw new Exception('Was not able to create items table in '.MODULE.'.db!');
		}
	} else {
		$db = new PDO('sqlite:db/'.MODULE.'.db');
	}
	return $db;

}

class Mindmap extends UmbrellaObjectWithId{
	static function table(){
		return [
			'id'    => ['INTEGER','KEY'=>'PRIMARY'],
			'title' => ['VARCHAR'=>255,'NOT NULL'],
			'data'  => 'TEXT'
		];
	}
	
	static function index(){
	    global $user;
	    $data = ['text'=>'Index'];
	    $sql = 'SELECT id,title FROM mindmaps LEFT JOIN users ON mindmaps.id = users.mindmap_id WHERE user_id = :u ORDER BY title ASC, id ASC';
	    $query = get_or_create_db()->query($sql);
	    $args = [':u'=>$user->id];
	    if (!$query->execute($args)) throw new Exception("Was not able to read mindmap list!");
	    $rows = $query->fetchAll(INDEX_FETCH);
	    $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	    foreach ($rows as $id => $row) $data['children'][] = ['text'=>$row['title'],'url'=>$url.'?id='.$id];	    
	    $result = (new Mindmap())->patch(['data'=>json_encode($data),'title'=>'Index']);
	    return $result;
	}
	
	static function load(){
	    global $user;
	    if (empty($user)) return (new Mindmap())->setContent(json_encode(["text"=>"Nicht eingeloggt!"]));
	    
	    $id = param("id");
	    if (empty($id)) return Mindmap::index();
	    
	    $sql = 'SELECT mindmaps.id as id,* FROM mindmaps LEFT JOIN users ON mindmaps.id = users.mindmap_id WHERE mindmaps.id = :i AND user_id = :u ';
	    $args = [':i'=>$id,':u'=>$user->id];
	    $db = get_or_create_db();
	    $query = $db->prepare($sql);
	    if (!$query->execute($args)) throw new Exception("Was not able to laod Mindmap!");
	    $rows = $query->fetchAll(INDEX_FETCH);
	    foreach ($rows as $id => $row){
	        $mindmap = (new Mindmap())->patch($row);
	        unset($mindmap->dirty);
	        return $mindmap;
	    }
	    return null;
	}
	
	static function save(){
	    global $user;
	    if (empty($user)) return (new Mindmap())->setContent(json_encode(["text"=>"Nicht eingeloggt!"]));
	    $data = param("data");
	    if (empty($data)) return (new Mindmap())->setContent(json_encode(["text"=>"Keine Daten übergeben!"]));
	    $title = param("title");
	    if (empty($title)) $title = "NEW MINDMAP";
	    $id = param("id");
	    if (empty($id)){
	        // create new
	        return (new Mindmap())->patch(['data'=>$data,'title'=>$title])->saveNew();
	    } else {
	        // update
	        return Mindmap::load()->patch(['data'=>$data,'title'=>$title])->update();	        
	    }	    
	}
	/***** end of static functions ******/
	
	function saveNew(){
	    global $user;
	    $sql = 'INSERT INTO mindmaps (title,data) VALUES ( :t , :d )';
	    $args = [':t'=>$this->title,":d"=>$this->data];
	    $db = get_or_create_db();
	    $query = $db->prepare($sql);
	    if (!$query->execute($args)) throw new Exception("Was not able to store new mindmap!");
	    $this->id = $db->lastInsertId();
	    $sql = 'INSERT INTO users (user_id, mindmap_id, permission) VALUES (:u, :m, :p )';
	    $args = [':u'=>$user->id,':m'=>$this->id,':p'=>READ_PERMISSION|WRITE_PERMISSION];
	    $query = $db->prepare($sql);
	    if (!$query->execute($args)) throw new Exception("Was not able to assign user to new mindmap!");
	    unset($this->dirty);
	    return $this;
	}

	function setContent($content){
	    $this->data = $content;
	    return $this;
	}
	
	function update(){
	    if (!empty($this->dirty)) {
    	    $fields = [];
    	    $args = [':id'=>$this->id];
    	    foreach (Mindmap::table() as $field => $definition){
    	        if ($field == 'id' || empty($this->{$field})) continue;
    	        if (in_array($field, $this->dirty)){
    	            $args[':'.$field] = $this->{$field};
    	            $fields[] = $field.' = :'.$field;
    	        }
    	    }
    	    $sql = 'UPDATE mindmaps SET '.implode(", ", $fields).' WHERE id = :id ';
    	    $db = get_or_create_db();
    	    $query = $db->prepare($sql);
       	    if (!$query->execute($args)) throw new Exception("Was not able to update mindmap!");    	    
	    }
	    unset($this->dirty);
	    return $this;
	}
	
}

class User extends UmbrellaObject{
	static function table(){
		return [
			'user_id'    => ['INTEGER','NOT NULL'],
			'mindmap_id' => ['INTEGER','NOT NULL'],
			'permission' => ['INT','NOT NULL','DEFAULT',READ_PERMISSION]
		];
	}
}

?>
