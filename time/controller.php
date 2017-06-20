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
?>
