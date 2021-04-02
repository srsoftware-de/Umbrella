<?php include '../bootstrap.php';

const MODULE = 'Logging';
const READ_PERMISSION = 1;
const WRITE_PERMISSION = 2;
$title = t('Umbrella Log Management');

function create_table($db,$table,$fields){
    $sql = 'CREATE TABLE '.$table.' ( ';
    foreach ($fields as $field => $props){
        if ($field == 'UNIQUE'||$field == 'PRIMARY KEY') {
            $field .='('.implode(',',$props).')';
            $props = null;
        }
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

function get_or_create_db(){
	if (!file_exists('.db') && !mkdir('.db')) throw new Exception('Failed to create '.MODULE.'/.db directory!');
	if (!is_writable('.db')) throw new Exception('Directory '.MODULE.'/.db not writable!');
	if (!file_exists('.db/'.MODULE.'.db')){
		$db = new PDO('sqlite:.db/'.MODULE.'.db');

		$tables = [
		    'data_sources'=>DataSource::table(),
		    'user_access'=>DataSource::user_access_table(),
		    'token_access'=>DataSource::token_access_table()
		];

		foreach ($tables as $table => $fields) create_table($db,$table, $fields);
		
	} else {
		$db = new PDO('sqlite:.db/'.MODULE.'.db');
	}
	return $db;
}

class DataSource extends UmbrellaObject{
    static function table(){
        return [
            'id'   => ['VARCHAR'=>255,'KEY'=>'PRIMARY'],
            'name' => ['VARCHAR'=>255,'NOT NULL'],
            'description' => 'TEXT'
        ];
    }
    
    static function user_access_table(){
        return [
            'user_id' => ['INTEGER','NOT NULL'],
            'prefix'  => ['VARCHAR'=>255,'NOT NULL'],
            'PRIMARY KEY' => ['user_id','prefix']
        ];
    }
    
    static function token_access_table(){
        return [
            'token' => ['VARCHAR'=>255,'KEY'=>'PRIMARY'],
            'prefix' => ['VARCHAR'=>255,'NOT NULL'],
            'expiration' => 'LONG'
        ]; 
    }
    
    function create_table($measure_type){
        create_table($this->db, 'measures', [
            'firstDB' => ['LONG','KEY'=>'PRIMARY'],
            'lastDB' => ['LONG','NOT NULL'],
            'firstSource' => ['LONG','NOT NULL'],
            'lastSource' => ['LONG','NOT NULL'],
            'value' => $measure_type,
            'count' => ['INTEGER','DEFAULT'=>1]
        ]);        
    }
    
    function __construct($uri){
        if (!ctype_alnum(str_replace('_', '', $uri))) throw new Exception('Provided uri is not allowed');
        $this->uri = $uri;        
        $dbName = $this->dbName();
        if (!file_exists($dbName)){
            $this->db = new PDO('sqlite:'.$dbName);
            $this->create_table('INTEGER');
        } else $this->db = new PDO('sqlite:'.$dbName);
    }
    
        
    function dbName(){
        return 'db/'.$this->uri.'.db';        
    }
    
    function latest($token){
        $sql = 'SELECT * FROM measures ORDER BY firstDB DESC LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($rows)<1) return NULL;
        $measure = new Measure();
        $measure->patch(reset($rows));
        unset($measure->dirty);
        return $measure;
    }
}

class Measure extends UmbrellaObject{
    function increment($data_source){
        $this->patch(['count'=>$this->count+1]);
        
        $parts = [];
        $args = [':fd'=>$this->firstDB];
        foreach ($this->dirty as $field) {
            $parts[] = $field.' = :'.$field;
            $args[':'.$field] = $this->{$field};
        }
        $sql = 'UPDATE measures SET ' . implode(', ', $parts) . ' WHERE firstDB = :fd';
        $query = $data_source->db->prepare($sql);
        if (!$query->execute($args)) throw new Exception();
        unset($this->dirty);
        return $this;
    }
    
    function save($data_source){
        $sql = 'INSERT INTO measures (firstDB, lastDB, firstSource, lastSource, value) VALUES (:fd, :ld, :fs, :ls, :vl )';
        $args = [':fd'=>$this->firstDB,':ld'=>$this->lastDB,':fs'=>$this->firstSource,':ls'=>$this->lastSource,':vl'=>$this->value];
        $query = $data_source->db->prepare($sql);
        if (!$query->execute($args)) throw new Exception();
        unset($this->dirty);
        return $this;
    }
}

function save(){    
    // discover, if user is logged in
    $user = empty($_SESSION['token']) ? null : getLocallyFromToken();
    if ($user === null) validateToken('logging');
    debug($user === null ? "logging anonymously" : "you are logging as ".$user->login);
    
    
    $db_time = time();
    $id = param('id');
    if (!$id) die('no id'); // TODO: sinnvolle message zurückgeben
    $data_source = new DataSource($id);
    
    if ($json = param('json')){
        $data = json_decode($json,JSON_OBJECT_AS_ARRAY); 
    } else {
        $data = array_merge($_GET,$_POST);
    }
    
    $source_time = $data['time'];
    $value = $data['val'];
    $token = $data['token'];
    if (!$value) die('no value');
    
    $latest_measure = $data_source->latest($token);
    if ($latest_measure->value == $value){
        $latest_measure->patch(['lastDB'=>$db_time,'lastSource'=>$source_time])->increment($data_source);
        return $latest_measure;
    } else {
        $measure = new Measure();
        $measure->patch(['firstDB'=>$db_time,'lastDB'=>$db_time,'firstSource'=>$source_time,'lastSource'=>$source_time,'value'=>$value]);
        $measure->save($data_source);
        return $measure;
    }
}

?>
