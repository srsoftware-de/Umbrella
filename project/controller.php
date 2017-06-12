<?php
	function get_or_create_db(){
		if (!file_exists('db')){
			assert(mkdir('db'),'Failed to create project/db directory!');
		}
		assert(is_writable('db'),'Directory project/db not writable!');
		if (!file_exists('db/projects.db')){
			$db = new PDO('sqlite:db/projects.db');
			$db->query('CREATE TABLE projects (id INTEGER PRIMARY KEY, name VARCHAR(255) NOT NULL, description TEXT);');
			$db->query('CREATE TABLE projects_users (project_id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY(project_id, user_id));');
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
?>
