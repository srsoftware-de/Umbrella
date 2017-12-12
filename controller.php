<?php

	const PROJECT_PERMISSION_OWNER = 1;
	const PROJECT_PERMISSION_PARTICIPANT = 2;

	const PROJECT_STATUS_OPEN = 10;
	const PROJECT_STATUS_STARTED = 20;
	const PROJECT_STATUS_PENDING = 40;
	const PROJECT_STATUS_COMPLETE = 60;
	const PROJECT_STATUS_CANCELED = 100;

	const TASK_STATUS_OPEN = 10;
	const TASK_STATUS_STARTED = 20;
	const TASK_STATUS_PENDING = 40;
	const TASK_STATUS_COMPLETE = 60;
	const TASK_STATUS_CANCELED = 100;

	$PROJECT_STATES = array(PROJECT_STATUS_CANCELED => 'canceled',
			PROJECT_STATUS_PENDING => 'pending',
			PROJECT_STATUS_OPEN => 'open',
			PROJECT_STATUS_COMPLETE => 'completed',
			PROJECT_STATUS_STARTED => 'started'
	);

	$PROJECT_PERMISSIONS = array(PROJECT_PERMISSION_OWNER=>'owner',PROJECT_PERMISSION_PARTICIPANT=>'participant');

	function get_or_create_db(){
		if (!file_exists('db')){
			assert(mkdir('db'),'Failed to create project/db directory!');
		}
		assert(is_writable('db'),'Directory project/db not writable!');
		if (!file_exists('db/projects.db')){
			$db = new PDO('sqlite:db/projects.db');
			$db->query('CREATE TABLE projects (id INTEGER PRIMARY KEY, company_id INT, name VARCHAR(255) NOT NULL, description TEXT, status INT DEFAULT '.PROJECT_STATUS_OPEN.');');
			$db->query('CREATE TABLE projects_users (project_id INT NOT NULL, user_id INT NOT NULL, permissions INT DEFAULT 1, PRIMARY KEY(project_id, user_id));');
		} else {
			$db = new PDO('sqlite:db/projects.db');
		}
		return $db;
	}

	function add_project($name,$description = null,$company_id=null){
		global $user;
		$db = get_or_create_db();
		assert($name !== null && trim($name) != '','Project name must not be empty or null!');
		$query = $db->prepare('INSERT INTO projects (name, company_id, description, status) VALUES (:name, :cid, :desc, :state);');
		assert($query->execute(array(':cid'=>$company_id,':name'=>$name,':desc'=>$description,':state'=>PROJECT_STATUS_OPEN)),'Was not able to create new project entry in  database');
		$project_id = $db->lastInsertId();
		add_user_to_project($project_id,$user->id,PROJECT_PERMISSION_OWNER);
	}

	function update_project($id,$name,$description = null,$company_id = null){
		$db = get_or_create_db();
		assert(is_numeric($id),'invalid project id passed!');
		assert($name !== null && trim($name) != '','Project name must not be empty or null!');
		$query = $db->prepare('UPDATE projects SET company_id = :cid, name = :name, description = :desc WHERE id = :id;');
		assert($query->execute(array(':id' => $id, ':cid'=>$company_id, ':name'=>$name,':desc'=>$description)),'Was not able to alter project entry in database');
	}

	function set_project_state($project_id, $state){
		$db = get_or_create_db();
		assert(is_numeric($project_id),'invalid project id passed!');
		assert(is_numeric($state),'invalid state passed!');
		$query = $db->prepare('UPDATE projects SET status = :state WHERE id = :id;');
		assert($query->execute(array(':state' => $state,':id'=>$project_id)),'Was not able to alter project state in database');
	}
	
	function load_projects($options = array()){
		global $user;
		$sql = 'SELECT id,* FROM projects WHERE id IN (SELECT project_id FROM projects_users WHERE user_id = ?)';
		$args = [$user->id];
		
		if (isset($options['ids'])){
			$ids = $options['ids'];
			if (!is_array($ids)) $ids = [$ids];
			$qMarks = str_repeat('?,', count($ids)-1).'?';
			$sql .= ' AND id IN ('.$qMarks.')';
			$args = array_merge($args, $ids); 
		}
		
		if (isset($options['company_ids'])){
			$ids = $options['company_ids'];
			if (!is_array($ids)) $ids = [$ids];
			$qMarks = str_repeat('?,', count($ids)-1).'?';
			$sql .= ' AND comapny_id IN ('.$qMarks.')';
			$args = array_merge($args, $ids);
		}

		if (isset($options['order'])){
			switch ($options['order']){
				case 'name':
				case 'status':
					$sql .= ' ORDER BY '.$options['order'].' COLLATE NOCASE';
					break;
				case 'company':
					$sql .= ' ORDER BY company_id DESC';
					break;
			}
		}

		$db = get_or_create_db();
		$query = $db->prepare($sql);
		assert($query->execute($args),'Was not able to load projects!');
		$projects = $query->fetchAll(INDEX_FETCH);
		if (isset($options['single']) && $options['single']) return reset($projects);
		return $projects;
	}

	function load_users($project_ids = null){
		assert($project_ids !== null,'No project id passed to load_project!');
		if (!is_array($project_ids)) $project_ids = [$project_ids];
		$db = get_or_create_db();
		$qmarks = implode(',', array_fill(0, count($project_ids), '?'));
		
		$query = $db->prepare('SELECT user_id, permissions FROM projects_users WHERE project_id in ('.$qmarks.')');
		assert($query->execute($project_ids));
		$results = $query->fetchAll(INDEX_FETCH);
		return $results;
	}

	function add_user_to_project($project_id = null,$user_id = null,$permission = null){
		assert(is_numeric($project_id),'project id must be numeric, is '.$project_id);
		assert(is_numeric($user_id),'user id must be numeric, is '.$user_id);
		assert(is_numeric($permission),'permission must be numeric, is '.$permission);
		$db = get_or_create_db();
		$query = $db->prepare('INSERT INTO projects_users (project_id, user_id, permissions) VALUES (:pid, :uid, :perm);');
		assert($query->execute(array(':pid'=>$project_id,':uid'=>$user_id, ':perm'=>$permission)),'Was not able to assign project to user!');
	}
	
	function remove_user($project_id,$user_id){
		$db = get_or_create_db();
		$query = $db->prepare('DELETE FROM projects_users WHERE project_id = :pid AND user_id = :uid');
		assert($query->execute([':pid'=>$project_id,':uid'=>$user_id]),'Was not able to remove user from project!');
		info('User has been removed from project.');		 
	}
?>
