<?php include '../bootstrap.php';

/** @var $dummy used in lopps */
/** @var $title used in importing classes */
const MODULE = 'Notes';
$title = t('Umbrella Notes Management');

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
								$sql.= 'DEFAULT '.($prop_v === null?'NULL ':'"'.$prop_v.'" '); break;
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
			assert($db->query($sql),'Was not able to create '.$table.' table in companies.db!');
		}
	} else {
		$db = new PDO('sqlite:db/notes.db');
	}
	return $db;
}

class Note extends UmbrellaObjectWithId{

	static function load($options = []){
		global $user;
		$db = get_or_create_db();

		$single = false;
		$user_dependent = true;
		$where = [];
		$args = [];

		$sql = 'SELECT * FROM notes';

		if (isset($options['key'])){
			$key = '%'.$options['key'].'%';
			$where[] = 'note LIKE ?';
			$args = array_merge($args, [$key]);
			$user_dependent = false;
			$options['limit'] = -1;
		}

		if (isset($options['ids'])){
			$ids = $options['ids'];
			if (!is_array($ids)) {
				$single = true;
				$ids = [$ids];
			}
			$qMarks = str_repeat('?,', count($ids)-1).'?';
			$where[] = 'id IN ('.$qMarks.')';
			$args = array_merge($args, $ids);
		}

		if (isset($options['uri'])){
			$uri = $options['uri'];
			$parts = explode(':', $uri,2); // was disabled, as model uses uris of the form model:project:xyz
			$module = array_shift($parts);
			$id = array_shift($parts);
			//debug(['uri'=>$uri,'parts'=>$parts,'module'=>$module,'id'=>$id]);
			switch ($module){
				case 'files':
					break; // do not call load for files
				case 'model': // uris are of the form model:project:<project id> or model:<model id> or model:diagram:<diagram id>
					if (strpos($id, 'diagram:')===0) break;
				case 'time':
					if (strpos($id, 'project:')===0){ // in the first case: request project
						$parts = explode(':', $id);
						$entities = request('project','json',['ids'=>array_pop($parts)]);
						if (empty($entities)) return [];
						break;
					}
				// uri is of the form model:xyz, go to default section
				default:
					$entities = request($module,'json',['ids'=>$id]);
					if (empty($entities)) return []; // only deliver notes for objects the user has access to!
					break;
			}
			$where[] = 'uri = ?';
			$args = array_merge($args,[$uri]);
			$user_dependent = false;
		}

		if ($user_dependent){
			$where[] = 'user_id = ?';
			$args[] = $user->id;
		}

		if (!empty($where)) $sql .= ' WHERE '.implode(' AND ', $where);

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
		//debug(query_insert($sql, $args));
		assert($query->execute($args),'Was not able to load notes');
		$rows = $query->fetchAll(INDEX_FETCH);
		$notes = [];
		foreach ($rows as $id => $row){
			$note = new Note();
			$note->id = $id;
			$note->patch($row);
			unset($note->dirty);
			if ($single) return $note;
			$notes[$id] = $note;
		}

		if ($single) return null; // no note found, which implies the loop is skipped

		if (isset($options['key'])){
			// this does a user-independent search. now we have to filter out those notes, to which the user has no access
			$docs = [];
			$files = [];
			$models = [];
			$project_ids = [];
			$stock_items = [];
			$task_ids = [];
			$wiki_ids = [];

			$companies = request('company','json');
			$accessible_company_ids = empty($companies) ? [] : array_keys($companies);
			unset($companies);

			$projects = request('project','json');
			$accessible_project_ids = empty($projects) ? [] : array_keys($projects);
			unset($projects);

			$accessible_uris = [];

			foreach ($notes as $note){
				$uri_parts = explode(':', $note->uri);
				$realm = array_shift($uri_parts);
				$id = implode(':', $uri_parts);;
				switch($realm){
					case 'document':
						$docs[$id] = true;
						break;
					case 'files':
						$files[$id] = true;
						break;
					case 'model':
						$models[$id] = true;
						break;
					case 'project':
						$project_ids[$id]=true;
						break;
					case 'stock':
						$stock_items[$id]=true;
						break;
					case 'task':
						$task_ids[$id]=true;
						break;
					case 'wiki':
						$wiki_ids[$id]=true;
						break;
					default:
						debug(['realm'=>$realm,implode(':', $uri_parts)]);
				}
			}

			if (!empty($docs)){
				$docs = request('document','json',['ids'=>array_keys($docs)]);
				foreach ($docs as $id => $dummy) $accessible_uris[] = 'document:'.$id;
			}

			if (!empty($files)){
				foreach ($files as $path => $dummy){
					$parts = explode(DS, $path);
					$realm = array_shift($parts);
					$id = array_shift($parts);
					switch ($realm){
						case 'company':
							if (in_array($id, $accessible_company_ids)) $accessible_uris[] = 'files:'.$realm.DS.$id.DS.implode(DS, $parts);
							break;
						case 'project':
							if (in_array($id, $accessible_project_ids)) $accessible_uris[] = 'files:'.$realm.DS.$id.DS.implode(DS, $parts);
							break;
						case 'user':
							if ($id == $user->id) $accessible_uris[] = 'files:'.$realm.DS.$id.DS.implode(DS, $parts);
							break;
					}
				}
			}

			if (!empty($models)){
				$mids = [];
				foreach ($models as $model_id => $dummy){
					if (strpos($model_id,'project:')===0){
						$pid = substr($model_id,8);
						if (in_array($pid, $accessible_project_ids)) $accessible_uris[] = 'model:'.$model_id;
					} else $mids[$model_id] = true;
				}
				if (!empty($mids)){
					$models = request('model','json',['ids'=>array_keys($mids)]);
					foreach ($models as $mid => $dummy) $accessible_uris[] = 'model:'.$mid;
				}
			}

			if (!empty($project_ids)){
				foreach ($project_ids as $pid => $dummy){
					if (in_array($pid, $accessible_project_ids)) $accessible_uris[] = 'project:'.$pid;
				}
			}

			if (!empty($stock_items)){
				foreach ($stock_items as $item_id => $dummy){
					$parts = explode(':', $item_id);
					$realm = array_shift($parts);
					$id = array_shift($parts);
					switch ($realm){
						case 'company':
							if (in_array($id, $accessible_company_ids)) $accessible_uris[] = 'stock:'.$item_id;
							break;
						case 'user':
							if ($id == $user->id) $accessible_uris[] = 'stock:'.$item_id;
							break;
					}
				}
			}

			if (!empty($task_ids)){
				$tasks = request('task','json',['ids'=>array_keys($task_ids)]);
				foreach ($tasks as $tid => $dummy) $accessible_uris[] = 'task:'.$tid;
			}

			if (!empty($wiki_ids)){
				$pages = request('wiki','json',['ids'=>array_keys($wiki_ids)]);
				foreach ($pages as $pid => $dummy) $accessible_uris[] = 'wiki:'.$pid;
			}

			foreach ($notes as $id => $note){
				if (!in_array($note->uri, $accessible_uris))	unset($notes[$id]);
			}
		}

		return $notes;
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

	function notify(){
		global $user;
		$recipients = param('recipients');
		if (empty($recipients)) return;
		$recipients = explode(',', $recipients);
		$context = param('context');
		$body = $this->url() . " :\n\n" . $this->note;
		$subject = t(empty($context)?'◊ added a note':'◊ added a note to ◊',[$user->login,$context]);
		$message = ['recipients'=>$recipients,'subject'=>$subject,'body'=>$body];
		request('user','notify',$message);
	}

	function save(){
		global $user;
		$db = get_or_create_db();

		if (isset($this->id)){
			if (!empty($this->dirty)){
				$sql = 'UPDATE notes SET';
				$args = [':id'=>$this->id];
				foreach ($this->dirty as $field){
					$sql .= ' '.$field.'=:'.$field.',';
					$args[':'.$field] = $this->{$field};
				}
				$sql = rtrim($sql,',').' WHERE id = :id';
				$query = $db->prepare($sql);
				assert($query->execute($args),'Was no able to update note in database!');
			}
		} else {
			$sql = 'INSERT INTO notes (user_id, uri, note, timestamp) VALUES (:uid, :uri, :note, :time);';
			$args = [':uid'=>$user->id, ':uri'=>$this->uri, ':note'=>$this->note, ':time'=>time()];
			$query = $db->prepare($sql);
			assert($query->execute($args),'Was not able to save note!');
			$this->id = $db->lastInsertId();
		}
		return $this;
	}

	function url(){
		$parts = explode(':', $this->uri,2);
		$module = array_shift($parts);
		$id = array_shift($parts);
		//debug(['module'=>$module,'id'=>$id],1);
		switch ($module){
			case 'files':
				return getUrl($module,'?path='.$id.'&'.$param);
				break;
			case 'model':
			case 'time':
				if (strpos($id,'project:')===0) return getUrl($module,'?'.str_replace(':', '=', $id));
				break;
			case 'poll':
				return getUrl($module,'view?id='.$id.'&'.$param);
				break;
		}
		return getUrl($module,$id.'/view');
	}
}
