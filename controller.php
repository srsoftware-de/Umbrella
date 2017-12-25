<?php
function get_or_create_db(){
        if (!file_exists('db')) assert(mkdir('db'),'Failed to create notes/db directory!');
        assert(is_writable('db'),'Directory notes/db not writable!');
        if (!file_exists('db/notes.db')){
                $db = new PDO('sqlite:db/notes.db');

                $tables = [
                        'notes'=>Note::table(),
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
                $db = new PDO('sqlite:db/notes.db');
        }
        return $db;

}

class Note{

	static function load($options = []){
		global $user;
		$db = get_or_create_db();
		
		$sql = 'SELECT * FROM notes WHERE user_id = :uid';
		$args = [':uid' => $user->id];

		if (isset($options['url'])){
			$sql .= ' AND url = :url';
			$args[':url'] = $options['url'];
		}
	
		$query = $db->prepare($sql);
		assert($query->execute($args),'Was not able to load notes');
		return $query->fetchAll(INDEX_FETCH);
	}

	static function table(){
		return [ 'id' => ['INTEGER','KEY'=>'PRIMARY'],
			'user_id' => ['INT','NOT NULL'],
			'url' => ['VARCHAR'=>255, 'NOT NULL'],
			'note' => ['TEXT','NOT NULL']
		];
	}

	function __construct($url = null, $note = null){
		$this->url = $url;
		$this->note = $note;
	}

	function save(){
		global $user;
		$db = get_or_create_db();

		$sql = 'INSERT INTO notes (user_id, url, note) VALUES (:uid, :url, :note);';
		$args = [':uid'=>$user->id, ':url'=>$this->url, ':note'=>$this->note];
		$query = $db->prepare($sql);
		assert($query->execute($args),'Was not able to  save note!');

	}
}
