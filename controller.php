<?php

	const TASK_PERMISSION_OWNER = 1;
	const TASK_PERMISSION_PARTICIPANT = 2;
	
	const TASK_STATUS_OPEN = 10;
	const TASK_STATUS_STARTED = 20;
	const TASK_STATUS_PENDING = 40;
	const TASK_STATUS_COMPLETE = 60;
	const TASK_STATUS_CANCELED = 100;
	
	$TASK_STATES = array(TASK_STATUS_CANCELED => 'canceled',
						 TASK_STATUS_PENDING => 'pending',
						 TASK_STATUS_OPEN => 'open',
						 TASK_STATUS_COMPLETE => 'completed',
						 TASK_STATUS_STARTED => 'started'
						);
	$TASK_PERMISSIONS = array(TASK_PERMISSION_OWNER=>'owner',TASK_PERMISSION_PARTICIPANT=>'participant');

	function get_or_create_db(){
		if (!file_exists('db')){
			assert(mkdir('db'),'Failed to create task/db directory!');
		}
		assert(is_writable('db'),'Directory task/db not writable!');
		if (!file_exists('db/tasks.db')){
			$db = new PDO('sqlite:db/tasks.db');
			$db->query('CREATE TABLE tasks (id INTEGER PRIMARY KEY,
							project_id INTEGER NOT NULL, 
							parent_task_id INTEGER DEFAULT NULL, 
							name VARCHAR(255) NOT NULL, 
							description TEXT, 
							status INT DEFAULT '.TASK_STATUS_OPEN.',
							start_date DATE,
							due_date DATE);');
			$db->query('CREATE TABLE tasks_users (task_id INT NOT NULL, user_id INT NOT NULL, permissions INT DEFAULT '.self::TASK_PERMISSION_OWNER.', PRIMARY KEY(task_id, user_id));');
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
	
	function update_task_states($db){
		$date = '"'.date('Y-m-d').'"';
		$db->exec('UPDATE tasks SET status = '.TASK_STATUS_OPEN.' WHERE status = '.TASK_STATUS_PENDING.' AND start_date != "" AND start_date <= '.$date);
		$db->exec('UPDATE tasks SET status = '.TASK_STATUS_OPEN.' WHERE status = '.TASK_STATUS_PENDING.' AND due_date != "" AND due_date <= '.$date);
		$db->exec('UPDATE tasks SET status = '.TASK_STATUS_PENDING.' WHERE status = '.TASK_STATUS_OPEN.' AND start_date != "" AND start_date > '.$date);
	}
	
	function get_task_ids_of_user($ids = null){
		global $user;
		$sql = 'SELECT task_id FROM tasks_users WHERE ';
		$args = [];
		if (is_array($ids)){
			$qmarks = implode(',', array_fill(0, count($ids), '?'));
			$sql .= 'task_id IN ('.$qmarks.') AND ';
			$args = $ids;
		}
		$sql .= 'user_id = ?';
		$args[] = $user->id;
		$db = get_or_create_db();
		$query = $db->prepare($sql);
		assert($query->execute($args),'Was not able to read list of user-assigned tasks.');
		return array_keys($query->fetchAll(INDEX_FETCH));
	}
	
	function load_tasks($options = array()){
		global $user;
		$ids_only = isset($options['ids_only']) && $options['ids_only'];
		
		$sql = 'SELECT id';
		$where = [];
		$args = [];
		
		if (!$ids_only) $sql .= ',*';
		$sql .= ' FROM tasks';
		if (!$ids_only) { // if we request more than the task_ids: limit task list to user's tasks
			$where[] = 'id IN (SELECT task_id FROM tasks_users WHERE user_id = ?)';
			$args[] = $user->id;
		}
		
		if (isset($options['ids'])){
			$ids = $options['ids'];
			if (!is_array($ids)) $ids = [$ids];
			$qMarks = str_repeat('?,', count($ids)-1).'?';
			$where[] = 'id IN ('.$qMarks.')';
			$args = array_merge($args, $ids);
		}
	
		if (isset($options['project_ids'])){
			$ids = $options['project_ids'];
			if (!is_array($ids)) $ids = [$ids];
			$qMarks = str_repeat('?,', count($ids)-1).'?';
			$where[] = 'project_id IN ('.$qMarks.')';
			$args = array_merge($args, $ids);
		}
		
		if (!empty($where)) $sql .= ' WHERE '.implode(' AND ', $where);
	
		$db = get_or_create_db();
		//debug(query_insert($sql, $args),1);
		$query = $db->prepare($sql);
		assert($query->execute($args),'Was not able to load tasks!');
		$rows = $query->fetchAll(INDEX_FETCH);
		if (isset($options['single']) && $options['single']) return reset($rows);
		return $rows;
	}

	function get_tasks($selection = []){
		global $user,$TASK_STATES;
		assert(is_array($selection),'Options passed to get_tasks is not an array!');
		$db = get_or_create_db();
		update_task_states($db);
		
		$task_ids = null;
		$single = false;
		if (isset($selection['task_id']) && $single=true) $task_ids=[$selection['task_id']];
		if (isset($selection['id'])      && $single=true) $task_ids=[$selection['id']];
		if (isset($selection['task_ids'])) $task_ids=$selection['task_ids'];
		if (isset($selection['ids']))      $task_ids=$selection['ids'];

		$args = get_task_ids_of_user($task_ids);
	
		if (empty($args)) {
			$res =['name'=>'You are not allowed to access that task!'];
			if (!$single) $res = [0=>$res];
			return $res;
		}

		$qmarks = implode(',', array_fill(0, count($args), '?'));
		$sql = 'SELECT id,* FROM tasks WHERE id IN ('.$qmarks.')';
		if (isset($selection['project_id'])){
			$sql .= ' AND project_id = ?';
			$args[] = $selection['project_id'];
		}
		if (!isset($selection['order'])) $selection['order'] = 'due_date';
		$MAX_DATE = "'9999-99-99'";
		switch ($selection['order']){
			case 'due_date':
				$sql .= ' ORDER BY (CASE due_date WHEN "" THEN '.$MAX_DATE.' ELSE IFNULL(due_date,'.$MAX_DATE.') END), status COLLATE NOCASE';
				break;
			case 'name':
				$sql .= ' ORDER BY name COLLATE NOCASE';
				break;
			case 'project_id':
				$sql .= ' ORDER BY project_id, status, due_date COLLATE NOCASE';
				break;
			case 'parent_task_id':
				$sql .= ' ORDER BY project_id, parent_task_id, status, due_date COLLATE NOCASE';
				break;				
			case 'start_date':
				$sql .= ' ORDER BY (CASE start_date WHEN "" THEN '.$MAX_DATE.' ELSE IFNULL(start_date,'.$MAX_DATE.') END), status COLLATE NOCASE';
				break;
			case 'status':
				$sql .= ' ORDER BY status, due_date COLLATE NOCASE';
				break;
		}
		$query = $db->prepare($sql);
		assert($query->execute($args),'Was not able to request task list!');
		$results = $query->fetchAll(INDEX_FETCH);
		foreach ($results as &$task) $task['status_string'] = $TASK_STATES[$task['status']];
		if ($single) return reset($results);
		return $results;
	}
	
	function setTags($name,$task_id){
		if ($raw_tags = param('tags')){
			$raw_tags = explode(' ', str_replace(',',' ',$raw_tags));
			$tags = [];
			foreach ($raw_tags as $tag){
				if (trim($tag) != '') $tags[]=$tag;
			}
			request('bookmark','add',['url'=>getUrl('task').$task_id.'/view','comment'=>t('Task: ?',$name),'tags'=>$tags],1);
		}
	}

	function add_task($name,$description = null,$project_id = null,$parent_task_id = null, $start_date = null, $due_date = null){
		global $user,$services;
		$db = get_or_create_db();
		assert($name !== null && trim($name) != '','Task name must not be empty or null!');
		assert(is_numeric($project_id),'Task must reference project!');
		$start_stamp = null;
		$status = TASK_STATUS_OPEN;
		if ($start_date !== null && $start_date != ''){
			$start_stamp = strtotime($start_date);
			assert($start_stamp !== false,'Start date is not a valid date!');
			if ($start_stamp > time()) $status = TASK_STATUS_PENDING;
		}
		if ($due_date !== null && $due_date != ''){
			$due_stamp = strtotime($due_date);
			assert($due_stamp !== false,'Due date is not a valid date!');
			if ($start_stamp && $start_stamp > $due_stamp){
				$start_date = $due_date;
				info('Start date adjusted to match due date!');
			}
		}
		$query = $db->prepare('INSERT INTO tasks (name, project_id, parent_task_id, description, status, start_date, due_date) VALUES (:name, :pid, :parent, :desc, :state, :start, :due);');
		assert($query->execute(array(':name'=>$name,':pid'=>$project_id, ':parent'=>$parent_task_id,':desc'=>$description,':state'=>$status, 'start'=>$start_date, ':due'=>$due_date)),'Was not able to create new task entry in database');
		$task_id = $db->lastInsertId();
		add_user_to_task($task_id,$user->id,TASK_PERMISSION_OWNER);
		
		if (isset($services['bookmark'])) setTags($name,$task_id);
	}
	
	function update_task($id,$name,$description = null,$project_id = null,$parent_task_id = null,$start_date = null, $due_date = null){
		global $services;
		$db = get_or_create_db();
		assert(is_numeric($id),'invalid task id passed!');
		assert($name !== null && trim($name) != '','Task name must not be empty or null!');
		assert(is_numeric($project_id),'Task must reference project!');
		$start_stamp = null;
		if ($start_date !== null && $start_date != ''){
			$start_stamp = strtotime($start_date);
			assert($start_stamp !== false,'Start date is not a valid date!');
		}
		if ($due_date !== null && $due_date != ''){
			$due_stamp = strtotime($due_date);
			assert($due_stamp !== false,'Due date is not a valid date!');
			if ($start_stamp && $start_stamp > $due_stamp){
				$start_date = $due_date;
				info('Start date adjusted to match due date!');
			}
		}		
		
		$query = $db->prepare('UPDATE tasks SET name = :name, project_id = :pid, parent_task_id = :parent, description = :desc, start_date = :start, due_date = :due WHERE id = :id;');
		assert($query->execute(array(':id' => $id, ':name'=>$name,':pid'=>$project_id, ':parent'=>$parent_task_id,':desc'=>$description,':start'=>$start_date,':due'=>$due_date)),'Was not able to alter task entry in database');
		
		if (isset($services['bookmark'])) setTags($name,$id);
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
		global $TASK_STATES;
		$id = $task['id'];
		$db = get_or_create_db();
		$query = $db->prepare('SELECT * FROM tasks WHERE parent_task_id = :id');
		assert($query->execute(array(':id'=>$id)),'Was not able to query children of '.$task['name']);
		$child_tasks = $query->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_UNIQUE|PDO::FETCH_ASSOC);
		foreach ($child_tasks as $id => &$child_task){
			$child_task['id'] = $id;
			$child_task['status_string'] = $TASK_STATES[$child_task['status']];
			if ($levels) load_children($child_task,$levels -1);
		}
		if (!empty($child_tasks)) $task['children'] = $child_tasks;
	}

	function delete_task($id = null){
		$task = get_tasks(['id'=>$id]);
		$db = get_or_create_db();
		$args = array(':id'=>$id,':ptid'=>$task['parent_task_id']);
		$query = $db->prepare('UPDATE tasks SET parent_task_id = :ptid WHERE parent_task_id = :id');
	        assert($query->execute($args));
		
		$query = $db->prepare('DELETE FROM tasks WHERE id = :id');
		assert($query->execute(array(':id'=>$id)));
		$query = $db->prepare('DELETE FROM task_dependencies WHERE task_id = :id');
		assert($query->execute(array(':id'=>$id)));
		$query = $db->prepare('DELETE FROM tasks_users WHERE task_id = :id');
		assert($query->execute(array(':id'=>$id)));
		
	}
	
	function load_users(&$task,$project_users){
		$id = $task['id'];
		assert(is_numeric($id),'Invalid task id passed to load_users!');
		$db = get_or_create_db();
		$query = $db->prepare('SELECT * FROM tasks_users WHERE task_id = :id');
		assert($query->execute(array(':id'=>$id)));
		$results = $query->fetchAll(PDO::FETCH_ASSOC);
		
		$users = array();
		foreach ($results as $result){
			$user_id = $result['user_id'];
			$users[$user_id] = $project_users[$user_id];
			$users[$user_id]['permissions'] = $result['permissions'];
		}
		$task['users'] = $users;		
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

	function remove_user_from_task($user_id,$task_id){
		$db = get_or_create_db();
		$query = $db->prepare('DELETE FROM tasks_users WHERE task_id = :tid AND user_id = :uid;');
		assert($query->execute([':tid'=>$task_id,':uid'=>$user_id]),'Was not able to remove user from task.');
	}
	
?>
