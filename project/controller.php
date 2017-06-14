<?php

	const PROJECT_PERMISSION_OWNER = 1;
	
	const PROJECT_STATUS_OPEN = 1;

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
		$query = $db->prepare('SELECT * FROM projects LEFT JOIN projects_users ON projects.id = projects_users.project_id WHERE user_id = :uid');
		assert($query->execute(array(':uid'=>$user->id)),'Was not able to request project list!');
                $results = $query->fetchAll(PDO::FETCH_ASSOC);

		return $results;
	}

	function add_project($name,$description = null){
		global $user;
		$db = get_or_create_db();
		assert($name !== null && trim($name) != '','Project name must not be empty or null!');
		$query = $db->prepare('INSERT INTO projects (name, description, status) VALUES (:name, :desc, :state);');		
		assert($query->execute(array(':name'=>$name,':desc'=>$description,':state'=>PROJECT_STATUS_OPEN)),'Was not able to create new project entry in  database');
		$project_id = $db->lastInsertId();
		$query = $db->prepare('INSERT INTO projects_users (project_id, user_id, permissions) VALUES (:pid, :uid, :perm);');
		assert($query->execute(array(':pid'=>$project_id,':uid'=>$user->id, ':perm'=>PROJECT_PERMISSION_OWNER)),'Was not able to assign project to user!');
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
?>
