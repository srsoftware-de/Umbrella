<?php

	const PROJECT_PERMISSION_OWNER = 1;
	const PROJECT_PERMISSION_PARTICIPANT = 2;
	
	const PROJECT_STATUS_CANCELED = 0;
	const PROJECT_STATUS_OPEN = 1;
	const PROJECT_STATUS_CLOSE = 2;
	
	$PROJECT_PERMISSIONS = array(PROJECT_PERMISSION_OWNER=>'owner',PROJECT_PERMISSION_PARTICIPANT=>'participant');	

	function get_or_create_db(){
		if (!file_exists('db')){
			assert(mkdir('db'),'Failed to create project/db directory!');
		}
		assert(is_writable('db'),'Directory project/db not writable!');
		if (!file_exists('db/projects.db')){
			$db = new PDO('sqlite:db/projects.db');
			$db->query('CREATE TABLE projects (id INTEGER PRIMARY KEY, name VARCHAR(255) NOT NULL, description TEXT, status INT DEFAULT 1);');
			$db->query('CREATE TABLE projects_users (project_id INT NOT NULL, user_id INT NOT NULL, permissions INT DEFAULT 1, PRIMARY KEY(project_id, user_id));');
		} else {
			$db = new PDO('sqlite:db/projects.db');
		}
		return $db;
	}

	function get_project_list(){
		global $user;
		$db = get_or_create_db();
		$query = $db->prepare('SELECT * FROM projects WHERE id IN (SELECT project_id FROM projects_users WHERE user_id = :uid)');
		assert($query->execute(array(':uid'=>$user->id)),'Was not able to request project list!');
		$results = $query->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_UNIQUE|PDO::FETCH_ASSOC);
		return $results;
	}

	function add_project($name,$description = null){
		global $user;
		$db = get_or_create_db();
		assert($name !== null && trim($name) != '','Project name must not be empty or null!');
		$query = $db->prepare('INSERT INTO projects (name, description, status) VALUES (:name, :desc, :state);');		
		assert($query->execute(array(':name'=>$name,':desc'=>$description,':state'=>PROJECT_STATUS_OPEN)),'Was not able to create new project entry in  database');
		$project_id = $db->lastInsertId();
		add_user_to_project($project_id,$user->id,PROJECT_PERMISSION_OWNER);
	}
	
	function load_project($id = null){
		assert($id !== null,'No project id passed to load_project!');
		assert(is_numeric($id),'Invalid project id passed to load_project!');
		$db = get_or_create_db();
		$query = $db->prepare('SELECT * FROM projects WHERE id = :id');
		assert($query->execute(array(':id'=>$id)));
		$results = $query->fetchAll(PDO::FETCH_ASSOC);
		return $results[0];
	}
	
	function load_users($id = null){
		assert($id !== null,'No project id passed to load_project!');
		assert(is_numeric($id),'Invalid project id passed to load_project!');
		$db = get_or_create_db();
		$query = $db->prepare('SELECT user_id, permissions FROM projects_users WHERE project_id = :id');
		assert($query->execute(array(':id'=>$id)));
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
?>
