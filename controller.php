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

		$sql = 'SELECT * FROM notes WHERE user_id = ?';
		$args = [$user->id];

		if (isset($options['ids'])){
			$ids = $options['ids'];
			if (!is_array($ids)) $ids = [$ids];
			$qMarks = str_repeat('?,', count($ids)-1).'?';
			$sql .= ' AND id IN ('.$qMarks.')';
			$args = array_merge($args, $ids);
		}

		if (isset($options['key'])){
			$key = '%'.$options['key'].'%';
			$sql .= ' AND note LIKE ?';
			$args = array_merge($args, [$key]);
		}

		if (isset($options['uri'])){
			$uri = $options['uri'];
			$parts = explode(':', $uri,2);
			$module = array_shift($parts);
			$id = array_shift($parts);
			if ($module != 'files'){
				$entities = request($module,'json',['ids'=>$id]);
				if (empty($entities)) return [];
			}
			$sql = 'SELECT * FROM notes WHERE uri = ?';
			$args = [$uri];
		}

		$order = isset($options['order']) ? $options['order'] : 'di';

		switch ($order){
			case 'di': $sql.= ' ORDER BY id DESC'; break;
			case 'id': $sql.= ' ORDER BY id'; break;
			case 'uri': $sql.= ' ORDER BY uri'; break;
		}

		$limit = isset($options['limit']) ? $options['limit'] : '20';

		if ($limit > 0){
			$sql .= ' LIMIT ?';
			$args[] = $limit;
		}
		$query = $db->prepare($sql);
		assert($query->execute($args),'Was not able to load notes');
		return $query->fetchAll(INDEX_FETCH);
	}

	static function table(){
		return [ 'id' => ['INTEGER','KEY'=>'PRIMARY'],
			'user_id' => ['INT','NOT NULL'],
			'uri' => ['VARCHAR'=>255, 'NOT NULL'],
			'note' => ['TEXT','NOT NULL'],
			'timestamp' => 'INT',
		];
	}

	static function delete($id = null){
		if ($id === null){
			error('No id passed to Note::delete!');
			return false;
		}
		$db = get_or_create_db();
		$query = $db->prepare('DELETE FROM notes WHERE id = :id');
		return $query->execute([':id'=>$id]);
	}

	function __construct($uri = null, $note = null){
		$this->uri = $uri;
		$this->note = $note;
	}

	function save(){
		global $user;
		$db = get_or_create_db();

		$sql = 'INSERT INTO notes (user_id, uri, note, timestamp) VALUES (:uid, :uri, :note, :time);';
		$args = [':uid'=>$user->id, ':uri'=>$this->uri, ':note'=>$this->note, ':time'=>time()];
		$query = $db->prepare($sql);
		assert($query->execute($args),'Was not able to save note!');

	}

	function url(){
		$parts = explode(':', $this->uri,2);
		$module = array_shift($parts);
		$id = array_shift($parts);
		if ($module == 'files') return getUrl($module,'?path='.$id);
		return getUrl($module,$id.'/view');
	}
}
