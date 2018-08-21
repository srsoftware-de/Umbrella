<?PHp

	const TIME_PERMISSION_OWNER = 1;
	const TIME_PERMISSION_PARTICIPANT = 2;
	
	const MODULE = 'Time';
	
	const TIME_STATUS_STARTED = 10; // time tracking started
	const TIME_STATUS_OPEN = 20; // time tracking concluded. not invoiced
	const TIME_STATUS_PENDING = 40; // time track used in unsent invoice
	const TIME_STATUS_COMPLETE = 60; // time track used in sent invoice
	const TIME_STATUS_CANCELED = 100;
	
	const TIME_STATES = [TIME_STATUS_CANCELED => 'canceled',
						 TIME_STATUS_PENDING => 'pending',
						 TIME_STATUS_OPEN => 'open',
						 TIME_STATUS_COMPLETE => 'completed',
						 TIME_STATUS_STARTED => 'started'
						];
	$TIME_PERMISSIONS = array(TIME_PERMISSION_OWNER=>'owener',TIME_PERMISSION_PARTICIPANT=>'participant');
	
	function get_or_create_db(){
		$table_filename = 'times.db';
		if (!file_exists('db')) assert(mkdir('db'),'Failed to create time/db directory!');
		assert(is_writable('db'),'Directory time/db not writable!');
		if (!file_exists('db/'.$table_filename)){
			$db = new PDO('sqlite:db/'.$table_filename);
	
			$tables = [
				'times'=>Timetrack::table(),
				'task_times'=>Timetrack::task_table(),
			];
	
			foreach ($tables as $table => $fields){
				$sql = 'CREATE TABLE '.$table.' ( ';
				foreach ($fields as $field => $props){
					if ($field == 'UNIQUE') {
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
									$sql.= 'DEFAULT '.($prop_v === null)?'NULL ':('"'.$prop_v.'" '); break;
								case $prop_k==='KEY':
									assert($prop_v === 'PRIMARY','Non-primary keys not implemented in time/controller.php!');
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
				assert($db->query($sql),'Was not able to create '.$table.' table in '.$table_filename.'!');
			}
		} else {
			$db = new PDO('sqlite:db/'.$table_filename);
		}
		return $db;
	}
	
	class Timetrack{
		static function table(){
			return [
				'id'				=> ['INTEGERR','KEY'=>'PRIMARY'],
				'user_id'			=> ['INT','NOT NULL'],
				'subject'			=> ['VARCHAR'=>255,'NOT NULL'],
				'description'		=> 'TEXT',
				'start_time'		=> 'TIMESTAMP',
				'end_time'			=> 'TIMESTAMP',
				'state'				=> ['INT','NOT NULL','DEFAULT 10'],
			];
		}
		
		static function task_table(){
			return [
				'task_id'		=> ['INT','NOT NULL'],
				'time_id'		=> ['INT','NOT NULL'],
				'PRIMARY KEY'	=> '(task_id, time_id)',
			];
		}
		
		static function load($options){
			global $parsedown, $user;
			$ids_only = isset($options['ids_only']) && $options['ids_only'];
			
			$sql = 'SELECT id,* FROM times';
			$where = [];
			$args = [];
			$single = false;
			
			if (empty($options['task_ids'])) {
				$where[] = 'user_id = ?';
				$args[] = $user->id;
			}
			
			if (isset($options['ids'])){
				if (!is_array($options['ids'])) {
					$single = true;
					$options['ids'] = [$options['ids']];
				}
				$qMarks = str_repeat('?,', count($options['ids'])-1).'?';
				$where[] = 'id IN ('.$qMarks.')';
				$args = array_merge($args, $options['ids']);
			}
			
			if (isset($options['search'])){
				$key = '%'.$options['search'].'%';
				$where[] = ' (subject LIKE ? OR description LIKE ?)';
				$args = array_merge($args,[$key,$key]);
			}
		
			if (!empty($options['task_ids'])){
				$ids = $options['task_ids'];
				if (!is_array($ids)) $ids = [$ids];
				$qMarks = str_repeat('?,', count($ids)-1).'?';
				$where[] = 'id IN (SELECT time_id FROM task_times WHERE task_id IN ('.$qMarks.'))';
				$args = array_merge($args, $ids);
			}
			
			if (!empty($where)) $sql .= ' WHERE '.implode(' AND ', $where);
			
			if (isset($options['order'])){
				switch ($options['order']){
					case 'description':
					case 'end_time':
					case 'start_time':
					case 'state':
					case 'subject':
						$sql .= ' ORDER BY '.$options['order'];
				}
			} else {
				$sql .= ' ORDER BY state ASC, end_time DESC';
			}
		
			$db = get_or_create_db();
			//debug(query_insert($sql, $args),1);
			$query = $db->prepare($sql);
			//debug($query,1);
			assert($query->execute($args),'Was not able to load times!');
			$rows = $query->fetchAll(INDEX_FETCH);
			
			$times = [];
			$task_ids = [];
			foreach ($rows as $row){
				$time = new Timetrack();
				$time->patch($row);
				if ($parsedown) $time->description = $parsedown->parse($time->description);
				unset($time->dirty);
				$task_ids = array_merge($task_ids,$time->task_ids());
				$times[$time->id] = $time;
			}
			$task_ids = array_unique($task_ids);
			
			$project_ids = [];
			$tasks = request('task','json',['ids'=>$task_ids]);
			$projects = request('project','json',['ids'=>array_keys($project_ids)]);
			foreach ($tasks as &$task) $task['project'] = $projects[$task['project_id']];
				
			foreach ($times as &$time){
				foreach ($time->tasks as $task_id => $dummy) {
					$task = $tasks[$task_id];
					$project_ids[$task['project_id']] = true;
					$time->tasks[$task_id] = $task;
				}
				if ($single) return $time;
			}
			if ($single) return null;
			return $times;
		}
		
		function patch($data = array()){
			if (!isset($this->dirty)) $this->dirty = [];
			foreach ($data as $key => $val){
				if (!isset($this->{$key}) || $this->{$key} != $val) $this->dirty[] = $key;
				$this->{$key} = $val;
			}
			return $this;
		}
		
		function tasks(){
			if (empty($this->tasks)) $this->task_ids();
			$ids_of_missing_tasks = [];
			foreach ($this->tasks as $task_id => $task) {
				if (empty($task)) $ids_of_missing_tasks[] = $task_id;
			}
			if (!empty($ids_of_missing_tasks)){
				debug('re-loading tasks');
				$tasks = request('task','json',['ids'=>$ids_of_missing_tasks]);
				foreach ($tasks as $task_id => $task) $this->tasks[$task_id] = $task;
			}
			return $this->tasks;
		}
		
		function task_ids(){
			if (empty($this->tasks)) {
				$sql = 'SELECT task_id FROM task_times WHERE time_id = :tid';
				$args = [':tid'=>$this->id];
				$db = get_or_create_db();
				$query = $db->prepare($sql);
				assert($query->execute($args),'Was not able to load task ids!');
				$this->tasks = $query->fetchAll(INDEX_FETCH);
			}
			return array_keys($this->tasks);
		}
	}
	
	function state_text($state){
		$t = TIME_STATES;
		return $t[$state];
	}
	
	function appendDescription($time_id, $subject, $description){
		$db = get_or_create_db();
		$query = $db->prepare('UPDATE times SET subject = :subject, description = IFNULL(description,"") || :desc WHERE id = :tid');
		$args = [ ':subject' => $subject, ':desc' => $description."\n\n", ':tid'=>$time_id];
		if (!$query->execute($args)) warn('Was not able to update timetrack description.');
	}

	function assign_task($task = null,$time_id = null){
		appendDescription($time_id, $task['name'], $task['description']);
		$db = get_or_create_db();
		$query = $db->prepare('INSERT OR IGNORE INTO task_times (task_id, time_id) VALUES (:task, :time)');
		assert($query->execute(array(':task'=>$task['id'],':time'=>$time_id)),'Was not able to assign task to timetrack.');
	}

	function start_time($user_id = null){
		assert(is_numeric($user_id),'No valid user id passed to start_time');
		$db = get_or_create_db();
		$query = $db->prepare('INSERT INTO times (user_id, subject, start_time, state) VALUES (:uid, :subj, :start, :state)');
		assert($query->execute(array(':uid'=>$user_id,':subj'=>t('started timetrack'),':start'=>time(),':state'=>TIME_STATUS_STARTED)),'Was not able to create new time entry!');
		return $db->lastInsertId();
	}

	function drop_time($time_id = null){
		assert(is_numeric($time_id),'No valid time id passed to drop_time');
		$db = get_or_create_db();
		$query = $db->prepare('DELETE FROM times WHERE id = :tid');
		assert($query->execute(array(':tid'=>$time_id)),'Was not able to drop time entry!');
		$query = $db->prepare('DELETE FROM task_times WHERE time_id = :tid');
		assert($query->execute(array(':tid'=>$time_id)),'Was not able to drop task_time entry!');		
	}

	function load_times($options = array()){
		global $user;
		$ids_only = isset($options['ids_only']) && $options['ids_only'];
		
		$sql = 'SELECT id,*';
		$where = [];
		$args = [];
		
		$select_by_task_ids = (isset($options['task_ids']) && !empty($options['task_ids']));
		
		$sql .= ' FROM times';
		if (!$select_by_task_ids) {
			$where[] = 'user_id = ?';
			$args[] = $user->id;
		}
		
		if (isset($options['ids'])){
			$ids = $options['ids'];
			if (!is_array($ids)) $ids = [$ids];
			$qMarks = str_repeat('?,', count($ids)-1).'?';
			$where[] = 'id IN ('.$qMarks.')';
			$args = array_merge($args, $ids);
		}
		
		if (isset($options['key'])){
			$key = '%'.$options['key'].'%';
			$where[] = ' (subject LIKE ? OR description LIKE ?)';
			$args = array_merge($args,[$key,$key]);
		}
	
		if ($select_by_task_ids){
			$ids = $options['task_ids'];
			if (!is_array($ids)) $ids = [$ids];
			$qMarks = str_repeat('?,', count($ids)-1).'?';
			$where[] = 'id IN (SELECT time_id FROM task_times WHERE task_id IN ('.$qMarks.'))';
			$args = array_merge($args, $ids);
		}
		
		if (!empty($where)) $sql .= ' WHERE '.implode(' AND ', $where);
		
		if (isset($options['order'])){
			switch ($options['order']){
				case 'description':
				case 'end_time':
				case 'start_time':
				case 'state':
				case 'subject':
					$sql .= ' ORDER BY '.$options['order'];
			}
		} else {
			$sql .= ' ORDER BY state ASC, end_time DESC';
		}
	
		$db = get_or_create_db();
		//debug(query_insert($sql, $args),1);
		$query = $db->prepare($sql);
		//debug($query,1);
		assert($query->execute($args),'Was not able to load times!');
		$times = $query->fetchAll(INDEX_FETCH);
		
		if (isset($options['single']) && $options['single']) {
			foreach ($times as $time_id => $time){
				$query = $db->prepare('SELECT task_id FROM task_times WHERE time_id = :tid');
				assert($query->execute([':tid'=>$time_id]),'Was not able to load task ids associated with times!');
				$rows = $query->fetchAll(PDO::FETCH_ASSOC);
				foreach ($rows as $row) $time['task_ids'][] = $row['task_id'];
				return $time;
			}
		}
		
		$count = count($times);
		if ($count>0){
			$qMarks = str_repeat('?,',$count-1).'?';
			$query = $db->prepare('SELECT time_id,task_id FROM task_times WHERE time_id IN ('.$qMarks.')' );
			assert($query->execute(array_keys($times)),'Was not able to load task ids associated with times!');
		
			$rows = $query->fetchAll(PDO::FETCH_ASSOC);
			foreach ($rows as $row){
				$time_id = $row['time_id'];
				$times[$time_id]['task_ids'][] = $row['task_id'];
			}
		}
		
		return $times;
	}

	function update_time($time_id = null,$subject = null,$description = null,$start = null,$end = null,$state = TIME_STATUS_OPEN){
		assert(is_numeric($time_id),'Invalid time id passed to update_time!');
		assert($subject !== null,'Subject must not be null!');
		$start_time = strtotime($start);
		assert($start_time !== false,'Invalid start time passed to update_time!');

		$end_time = strtotime($end);
		if (!$end_time) $end_time = null;
		if ($end_time === null) $state = TIME_STATUS_STARTED;

		$db = get_or_create_db();
		$query = $db->prepare('UPDATE times SET subject = :sub, description = :desc, start_time = :start, end_time = :end, state = :state WHERE id = :tid');		
		assert($query->execute(array(':tid'=>$time_id,':sub'=>$subject,':desc'=>$description,':start'=>$start_time,':end'=>$end_time, ':state' => $state)),'Was not able to update time entry');
	}

	function get_open_tracks($user_id = null){
		assert(is_numeric($user_id),'No valid user id passed to get_open_tasks!');
		$db = get_or_create_db();
		$query = $db->prepare('SELECT * FROM times WHERE end_time IS NULL AND user_id = :uid');
		assert($query->execute(array(':uid'=>$user_id)),'Was not able to read open time tracks.');
		$tracks = $query->fetchAll(INDEX_FETCH);
		return $tracks;
	}

	function set_state($time_id = null,$state = TIME_STATUS_OPEN){
		global $user;
		assert($time_id !== null,'Invalid time id (null) submited to set_state!');
		if (!is_array($time_id)) $time_id = [$time_id];
		$db = get_or_create_db();
		$query = $db->prepare('UPDATE times SET state = :state WHERE user_id = :uid AND id = :id');
		foreach ($time_id as $id){
			assert($query->execute([':state'=>$state,':uid'=>$user->id,':id'=>$id]),'Was not able to update state of time '.$id.'!');
		}
	}
	
	$parsedown = null;
	if (file_exists('../lib/parsedown/Parsedown.php')){
		include '../lib/parsedown/Parsedown.php';
		$parsedown = Parsedown::instance();
	}
?>
