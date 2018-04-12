<?php
function get_or_create_db(){
	if (!file_exists('db')) assert(mkdir('db'),'Failed to create model/db directory!');
	assert(is_writable('db'),'Directory model/db not writable!');
	if (!file_exists('db/models.db')){
		$db = new PDO('sqlite:db/models.db');

		$tables = [
			'flows'=>    Flow::table(),
			'endpoint'=> Endpoint::table(),
			'models'=>   Model::fields(),
			'processes'=>Process::table(),
			'terminals'=>Terminal::table(),
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

class Flow{
	static function table(){
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
}
class Endpoint{
	static function table(){
		return [
			'id' => ['INTEGER','KEY'=>'PRIMARY'],
			'process_id' => ['INT','NOT NULL'],
			'direction' => ['BOOLEAN'],
			'name' => ['VARCHAR'=>255, 'NOT NULL'],
			'description' => ['TEXT'],
		];
	}
}

class Model{
	static function load($options = []){
		global $projects;
		$db = get_or_create_db();

		if (!isset($projects)) $projects = request('project','json');
		$project_ids = array_keys($projects);
		$qMarks = str_repeat('?,', count($project_ids)-1).'?';
		$sql = 'SELECT * FROM models WHERE project_id IN ('.$qMarks.')';
		$args = $project_ids;

		if (isset($options['ids'])){
			$ids = $options['ids'];
			if (!is_array($ids)) $ids = [$ids];
			$qMarks = str_repeat('?,', count($ids)-1).'?';
			$sql .= ' AND id IN ('.$qMarks.')';
			$args = array_merge($args, $ids);
		}

		$query = $db->prepare($sql);
		assert($query->execute($args),'Was not able to load models');
		$rows = $query->fetchAll(PDO::FETCH_ASSOC);
		$models = [];
		
		foreach ($rows as $id => $row){
			$model = new Model();
			$model->patch($row);
			$model->dirty=[];
			$models[$id] = $model;
		}
		
		return $models;
	}

	static function fields(){
		return [
			'id' => ['INTEGER','KEY'=>'PRIMARY'],
			'project_id' => ['INT','NOT NULL'],
			'name' => ['VARCHAR'=>255, 'NOT NULL'],
			'description' => ['TEXT'],
		];
	}
	
	function patch($data = array()){
		if (!isset($this->dirty)) $this->dirty = [];
		foreach ($data as $key => $val){
			if ($key === 'id' && isset($this->id)) continue;
			if (isset($this->{$key}) && $this->{$key} != $val) $this->dirty[] = $key;
			$this->{$key} = $val;
		}
	}

	function __construct($project_id = null, $name = null, $description = null){
		$this->project_id = $project_id;
		$this->name = $name;
		$this->description = $description;
	}	

	public function save(){
		global $user;
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
				redirect('../index');
			}
		} else {
			$known_fields = array_keys(Company::fields());
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
			redirect('index');
		}
	}
}

class Process{
	static function table(){
		return [
			'id' => ['INTEGER','KEY'=>'PRIMARY'],
			'model_id' => ['INT','NOT NULL'],
			'parent_id' => ['INT'],
			'name' => ['VARCHAR'=>255, 'NOT NULL'],
			'description' => ['TEXT'],
		];
	}
}
class Terminal{
	static function table(){
		return [
			'id' => ['INTEGER','KEY'=>'PRIMARY'],
			'model_id' => ['INT','NOT NULL'],
			'type' => ['INT'],
			'name' => ['VARCHAR'=>255, 'NOT NULL'],
			'description' => ['TEXT'],
		];
	}
}
