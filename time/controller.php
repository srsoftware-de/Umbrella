<?php

	const TIME_PERMISSION_OWNER = 1;
	const TIME_PERMISSION_PARTICIPANT = 2;
	
	const TIME_STATUS_OPEN = 10;
	const TIME_STATUS_STARTED = 20;
	const TIME_STATUS_PENDING = 40;
	const TIME_STATUS_COMPLETE = 60;
	const TIME_STATUS_CANCELED = 100;
	
	$time_states = array(TIME_STATUS_CANCELED => 'canceled',
						 TIME_STATUS_PENDING => 'pending',
						 TIME_STATUS_OPEN => 'open',
						 TIME_STATUS_COMPLETE => 'completed',
						 TIME_STATUS_STARTED => 'started'
						);
	$TIME_PERMISSIONS = array(TIME_PERMISSION_OWNER=>'owener',TIME_PERMISSION_PARTICIPANT=>'participant');

	function get_or_create_db(){
		if (!file_exists('db')){
			assert(mkdir('db'),'Failed to create time/db directory!');
		}
		assert(is_writable('db'),'Directory time/db not writable!');
		if (!file_exists('db/times.db')){
			$db = new PDO('sqlite:db/times.db');
			$db->query('CREATE TABLE times (id INTEGER PRIMARY KEY,
							user_id INTEGER NOT NULL,
							subject VARCHAR(255) NOT NULL,
							description TEXT,
							start_time TIMESTAMP,
							end_time TIMESTAMP);');
			$db->query('CREATE TABLE task_times (task_id INT NOT NULL, time_id INT NOT NULL, PRIMARY KEY(task_id, time_id));');
		} else {
			$db = new PDO('sqlite:db/times.db');
		}
		return $db;
	}

	function assign_task($task_id = null,$time_id = null){
		assert(is_numeric($task_id),'No valid task id passed to assign_task.');
		assert(is_numeric($time_id),'No valid time id passed to assign_task.');
		$db = get_or_create_db();
		$query = $db->prepare('INSERT OR IGNORE INTO task_times (task_id, time_id) VALUES (:task, :time)');
		assert($query->execute(array(':task'=>$task_id,':time'=>$time_id)),'Was not able to assign task to timetrack.');
	}

	function start_time($user_id = null){
		assert(is_numeric($user_id),'No valid user id passed to start_time');
		$db = get_or_create_db();
		$query = $db->prepare('INSERT INTO times (user_id, subject, start_time) VALUES (:uid, :subj, :start)');
		assert($query->execute(array(':uid'=>$user_id,':subj'=>'new time',':start'=>time())),'Was not able to create new time entry!');
	}

	function drop_time($time_id = null){
		assert(is_numeric($time_id),'No valid time id passed to drop_time');
		$db = get_or_create_db();
		$query = $db->prepare('DELETE FROM times WHERE id = :tid');
		assert($query->execute(array(':tid'=>$time_id)),'Was not able to drop time entry!');
		$query = $db->prepare('DELETE FROM task_times WHERE time_id = :tid');
		assert($query->execute(array(':tid'=>$time_id)),'Was not able to drop task_time entry!');		
	}

	function load_time($id = null){
		assert($id !== null,'No time id passed to load_time!');
		assert(is_numeric($id),'Invalid time id passed to load_time!');
		$db = get_or_create_db();
		$query = $db->prepare('SELECT * FROM times WHERE id = :id');
		assert($query->execute(array(':id'=>$id)));
		$results = $query->fetchAll(PDO::FETCH_ASSOC);
		return $results[0];
	}

	function get_time_list($sort = null){
		global $user;
		$db = get_or_create_db();
		$query = $db->prepare('SELECT * FROM times WHERE user_id = :uid');
		assert($query->execute(array(':uid'=>$user->id)),'Was not able to read time list from database!');
		$result = $query->fetchAll(INDEX_FETCH);
		return $result;
	}
	
	function get_time_assignments($ids = array()){
		$db = get_or_create_db();
		$sql = 'SELECT * FROM task_times';
		$args=array();
		if (is_array($ids) && !empty($ids)){
			$qMarks = str_repeat('?,', count($ids) - 1) . '?';
			$sql .= ' WHERE time_id IN ('.$qMarks.')';
			$args = $ids;
		}
		$query = $db->prepare($sql);
		assert($query->execute($args),'Was not able to load time assignments!');
		$results = $query->fetchAll(PDO::FETCH_ASSOC);
		$assignments = array();
		foreach ($results as $assignent){
			$time_id = $assignent['time_id'];
			$task_id = $assignent['task_id'];
			if (!isset($assignments[$time_id])) $assignent[$time_id]= array();
			$assignments[$time_id][$task_id] = null;
		}
		return $assignments;
	}

	function update_time($time_id = null,$subject = null,$description = null,$start = null,$end = null){
		assert(is_numeric($time_id),'Invalid time id passed to update_time!');
		assert($subject !== null,'Subject must not be null!');
		$start_time = strtotime($start);
		assert($start_time !== false,'Invalid start time passed to update_time!');

		$end_time = strtotime($end);
		if (!$end_time) $end_time = null;

		$db = get_or_create_db();
		$query = $db->prepare('UPDATE times SET subject = :sub, description = :desc, start_time = :start, end_time = :end WHERE id = :tid');		
		assert($query->execute(array(':tid'=>$time_id,':sub'=>$subject,':desc'=>$description,':start'=>$start_time,':end'=>$end_time)),'Was not able to update time entry');
	}

	function get_open_tracks($user_id = null){
		assert(is_numeric($user_id),'No valid user id passed to get_open_tasks!');
		$db = get_or_create_db();
		$query = $db->prepare('SELECT * FROM times WHERE end_time IS NULL AND user_id = :uid');
		assert($query->execute(array(':uid'=>$user_id)),'Was not able to read open time tracks.');
		$tracks = $query->fetchAll(INDEX_FETCH);
		return $tracks;
	}

	function load_tasks(&$time = null){
		assert($time !== null,'Time passed to load_tasks must not be null');
		$db = get_or_create_db();
		$query = $db->prepare('SELECT * FROM task_times WHERE time_id = :time');
		assert($query->execute(array(':time'=>$time['id'])),'Was not able to read tasks for timetrack.');
		$tasks = $query->fetchAll(INDEX_FETCH);
	    $tasks = request('task', 'json?ids='.implode(',',array_keys($tasks)));
		$time['tasks'] = $tasks;
	}
?>
