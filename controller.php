<?php include '../bootstrap.php';

const MODULE = 'Mindmaps';
const READ_PERMISSION = 1;
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
			$query = $db->prepare($sql);
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
