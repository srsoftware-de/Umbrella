<?php

	const TASK_PERMISSION_OWNER = 1;
	const TASK_PERMISSION_PARTICIPANT = 2;
	
	const TASK_STATUS_CANCELED = 100;
	const TASK_STATUS_PENDING = 40;
	const TASK_STATUS_OPEN = 10;
	const TASK_STATUS_COMPLETE = 60;
	const TASK_STATUS_STARTED = 20;
	$task_states = array(TASK_STATUS_CANCELED => 'canceled',
						 TASK_STATUS_PENDING => 'pending',
						 TASK_STATUS_OPEN => 'open',
						 TASK_STATUS_COMPLETE => 'completed',
						 TASK_STATUS_STARTED => 'started'
						);
	

	function get_or_create_db(){
		if (!file_exists('db')){
			assert(mkdir('db'),'Failed to create task/db directory!');
		}
		assert(is_writable('db'),'Directory task/db not writable!');
		if (!file_exists('db/tasks.db')){
			$db = new PDO('sqlite:db/tasks.db');
			$db->query('CREATE TABLE tasks (id INTEGER PRIMARY KEY, project_id INTEGER NOT NULL, parent_task_id INTEGER DEFAULT NULL, name VARCHAR(255) NOT NULL, description TEXT, status INT DEFAULT '.TASK_STATUS_OPEN.');');
			$db->query('CREATE TABLE tasks_users (task_id INT NOT NULL, user_id INT NOT NULL, permissions INT DEFAULT 1, PRIMARY KEY(task_id, user_id));');
			$db->query('CREATE TABLE task_dependencies (task_id INT NOT NULL, required_task_id INT NOT NULL, PRIMARY KEY(task_id, required_task_id));');
		} else {
			$db = new PDO('sqlite:db/tasks.db');
		}
		return $db;
	}
	
	function update_task_requirements($id,$required_task_ids){
		$db = get_or_create_db();
		$query = $db->prepare('INSERT INTO task_dependencies (task_id, required_task_id) VALUES (:id, :req);');
		foreach ($required_task_ids as $rid => $dummy){
			$query->execute(array(':id'=>$id,':req'=>$rid));
		}
	}

	function get_task_list($order = null, $project_id = null){
		global $user;
		$db = get_or_create_db();
		$sql = 'SELECT * FROM tasks WHERE id IN (SELECT task_id FROM tasks_users WHERE user_id = :uid)';
		$args = array(':uid'=>$user->id);
		if (is_numeric($project_id)){
			$sql .= ' AND project_id = :pid';
			$args[':pid'] = $project_id;
		}
		if ($order === null) $order = 'status';
		switch ($order){
			case 'project_id':
			case 'parent_task_id':
			case 'name':
			case 'status':
				$sql .= ' ORDER BY '.$order.' COLLATE NOCASE';
		}
		$query = $db->prepare($sql);		
		assert($query->execute($args),'Was not able to request project list!');
		$results = $query->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_UNIQUE|PDO::FETCH_ASSOC);
		return $results;
	}

	function add_task($name,$description = null,$project_id = null,$parent_task_id = null){
		global $user;
		$db = get_or_create_db();
		assert($name !== null && trim($name) != '','Task name must not be empty or null!');
		assert(is_numeric($project_id),'Task must reference project!');
		$query = $db->prepare('INSERT INTO tasks (name, project_id, parent_task_id, description, status) VALUES (:name, :pid, :parent, :desc, :state);');		
		assert($query->execute(array(':name'=>$name,':pid'=>$project_id, ':parent'=>$parent_task_id,':desc'=>$description,':state'=>TASK_STATUS_OPEN)),'Was not able to create new task entry in database');
		$task_id = $db->lastInsertId();
		add_user_to_task($task_id,$user->id,TASK_PERMISSION_OWNER);
	}
	
	function update_task($id,$name,$description = null,$project_id = null,$parent_task_id = null){
		$db = get_or_create_db();
		assert(is_numeric($id),'invalid task id passed!');
		assert($name !== null && trim($name) != '','Task name must not be empty or null!');
		assert(is_numeric($project_id),'Task must reference project!');
		$query = $db->prepare('UPDATE tasks SET name = :name, project_id = :pid, parent_task_id = :parent, description = :desc WHERE id = :id;');
		assert($query->execute(array(':id' => $id, ':name'=>$name,':pid'=>$project_id, ':parent'=>$parent_task_id,':desc'=>$description)),'Was not able to alter task entry in database');
	}
	
	function set_task_state($task_id, $state){
		$db = get_or_create_db();
		assert(is_numeric($task_id),'invalid task id passed!');
		assert(is_numeric($state),'invalid state passed!');
		$query = $db->prepare('UPDATE tasks SET status = :state WHERE id = :id;');
		assert($query->execute(array(':state' => $state,':id'=>$task_id)),'Was not able to alter task state in database');		
	}
	
	function load_requirements(&$task){
		$id = $task['id'];
		$db = get_or_create_db();
		$query = $db->prepare('SELECT * FROM tasks WHERE id IN (SELECT required_task_id FROM task_dependencies WHERE task_id = :id) ORDER BY status,name');
		assert($query->execute(array(':id'=>$id)),'Was not able to query requirements of '.$task['name']);
		$required_tasks = $query->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_UNIQUE|PDO::FETCH_ASSOC);
		if (!empty($required_tasks)) $task['requirements'] = $required_tasks;
	}
	
	function load_children(&$task,$levels = 0){
		$id = $task['id'];
		$db = get_or_create_db();
		$query = $db->prepare('SELECT * FROM tasks WHERE parent_task_id = :id');
		assert($query->execute(array(':id'=>$id)),'Was not able to query children of '.$task['name']);
		$child_tasks = $query->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_UNIQUE|PDO::FETCH_ASSOC);
		foreach ($child_tasks as $id => &$child_task){
			$child_task['id'] = $id;
			if ($levels) load_children($child_task,$levels -1);
		}
		if (!empty($child_tasks)) $task['children'] = $child_tasks;
	}
	
	function load_task($id = null){
		assert($id !== null,'No task id passed to load_task!');
		assert(is_numeric($id),'Invalid task id passed to load_task!');
		$db = get_or_create_db();
		$query = $db->prepare('SELECT * FROM tasks WHERE id = :id');
		assert($query->execute(array(':id'=>$id)));
		$results = $query->fetchAll(PDO::FETCH_ASSOC);
		return $results[0];
	}
	
	function load_users($id = null){
		assert(is_numeric($id),'Invalid task id passed to load_users!');
		$db = get_or_create_db();
		$query = $db->prepare('SELECT * FROM tasks_users WHERE task_id = :id');
		assert($query->execute(array(':id'=>$id)));
		$results = $query->fetchAll(PDO::FETCH_ASSOC);
		return $results;		
	}
	
	function add_user_to_task($task_id = null,$user_id = null,$permission = null){
		assert(is_numeric($task_id),'task id must be numeric, is '.$task_id);
		assert(is_numeric($user_id),'user id must be numeric, is '.$user_id);
		assert(is_numeric($permission),'permission must be numeric, is '.$permission);
		$db = get_or_create_db();
		$query = $db->prepare('INSERT INTO tasks_users (task_id, user_id, permissions) VALUES (:tid, :uid, :perm);');
		assert($query->execute(array(':tid'=>$task_id,':uid'=>$user_id, ':perm'=>$permission)),'Was not able to assign task to user!');
	}
	
	function find_project($task_id){
		$db = get_or_create_db();
		$query = $db->prepare('SELECT project_id, parent_task_id FROM tasks WHERE id = :id;');
		assert($query->execute(array(':id'=>$task_id)),'Was not able to read tasks parent or project');
		$data = reset($query->fetchAll(PDO::FETCH_ASSOC));
		if (isset($data['project_id'])) return $data['project_id'];
		if (isset($data['parent_task_id'])) return find_project($data['parent_task_id']);
		return null;
	}
?>
