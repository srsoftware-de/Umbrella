<?php
function get_or_create_db(){
        if (!file_exists('db')) assert(mkdir('db'),'Failed to create model/db directory!');
        assert(is_writable('db'),'Directory model/db not writable!');
        if (!file_exists('db/models.db')){
                $db = new PDO('sqlite:db/models.db');

                $tables = [
						'flows'=>    Flow::table(),
						'endpoint'=> Endpoint::table(),
                        'models'=>   Model::table(),
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
	static function table(){
		return [
			'id' => ['INTEGER','KEY'=>'PRIMARY'],
			'project_id' => ['INT','NOT NULL'],
			'name' => ['VARCHAR'=>255, 'NOT NULL'],
			'description' => ['TEXT'],
		];
	}
}class Process{
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
