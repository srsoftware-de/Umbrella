<?php

	const PROJECT_PERMISSION_OWNER = 1;
	const PROJECT_PERMISSION_PARTICIPANT = 2;

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
		
		if (isset($services['bookmark']) && ($raw_tags = param('tags'))){
			$raw_tags = explode(' ', str_replace(',',' ',$raw_tags));
			$tags = [];
			foreach ($raw_tags as $tag){
				if (trim($tag) != '') $tags[]=$tag;
			}
			request('bookmark','add',['url'=>getUrl('project').$project_id.'/view','comment'=>$name,'tags'=>$tags]);
		}
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

	function connected_users($options = []){
		global $user;
		$sql = 'SELECT user_id,* FROM projects_users WHERE project_id IN (SELECT project_id FROM projects_users WHERE user_id = ?)';
		$args = [$user->id];

		if (isset($options['ids'])){
			$ids = $options['ids'];
			if (!is_array($ids)) $ids = [$ids];
			$qmarks = str_repeat('?,', count($ids)-1).'?';
			$sql .= ' AND project_id IN ('.$qmarks.')';
			$args = array_merge($args,$ids);
		}

		$sql .= ' GROUP BY user_id';
		$db = get_or_create_db();
		$query = $db->prepare($sql);
		assert($query->execute($args),'Was not able to read connected users.');
		return $query->fetchAll(INDEX_FETCH);
	}

	function load_projects($options = array()){
		global $user;
		$sql = 'SELECT id,* FROM projects WHERE id IN (SELECT project_id FROM projects_users WHERE user_id = ?)';
		$args = [$user->id];
		
		$single = false;
		if (isset($options['ids'])){
			$ids = $options['ids'];			
			if (!is_array($ids)) {
				$ids = [$ids];
				$single = true;
			}
			$qMarks = str_repeat('?,', count($ids)-1).'?';
			$sql .= ' AND id IN ('.$qMarks.')';
			$args = array_merge($args, $ids); 
		}
		
		if (isset($options['company_ids'])){
			$ids = $options['company_ids'];
			if (!is_array($ids)) $ids = [$ids];
			$qMarks = str_repeat('?,', count($ids)-1).'?';
			$sql .= ' AND company_id IN ('.$qMarks.')';
			$args = array_merge($args, $ids);			
		}

		if (isset($options['order'])){
			switch ($options['order']){
				case 'status':
					$sql .= ' ORDER BY '.$options['order'].' COLLATE NOCASE';
					break;
				case 'company':
					$sql .= ' ORDER BY company_id DESC';
					break;
			}
		} else $sql .= ' ORDER BY name COLLATE NOCASE';

		$db = get_or_create_db();
		//debug(query_insert($sql, $args),1);
		$query = $db->prepare($sql);
		assert($query->execute($args),'Was not able to load projects!');
		$projects = $query->fetchAll(INDEX_FETCH);
		if ($single) return reset($projects);
		return $projects;
	}

	function load_users(&$projects){
		assert(is_array($projects),'No projects passed to load_project!');
		$db = get_or_create_db();
				
		$user_ids = [];
		$query = $db->prepare('SELECT user_id, permissions FROM projects_users WHERE project_id = :pid');

		
		if (isset($projects['id'])){
			assert($query->execute([':pid'=>$projects['id']]));
			$users = $query->fetchAll(INDEX_FETCH);
			$projects['users'] = $users;
			foreach ($users as $id => $permissions) $user_ids[$id] = true;
		} else {
			foreach ($projects as &$project){
				assert($query->execute([':pid'=>$project['id']]));
				$users = $query->fetchAll(INDEX_FETCH);
				$project['users'] = $users;
				foreach ($users as $id => $permissions) $user_ids[$id] = true;
			}
		}
		return array_keys($user_ids);
	}

	function add_user_to_project($project = null,$new_user = null,$permission = null){
		global $user;
		assert(is_array($project),'project id must be numeric, is '.$project_id);
		assert(is_array($new_user),'user id must be numeric, is '.$user_id);
		assert(is_numeric($permission),'permission must be numeric, is '.$permission);
		$db = get_or_create_db();
		$query = $db->prepare('INSERT INTO projects_users (project_id, user_id, permissions) VALUES (:pid, :uid, :perm);');
		assert($query->execute(array(':pid'=>$project['id'],':uid'=>$new_user['id'], ':perm'=>$permission)),'Was not able to assign project to user!');
		$sender = $user->email;
		$reciever = $new_user['email'];
		$subject = t('? added you to a project',$user->login);
		$text = t('You have been added to the project "?": ?',[$project['name'],getUrl('project',$project['id'].'/view')]);
		send_mail($sender, $reciever, $subject, $text);
	}
	
	function remove_user($project_id,$user_id){
		global $user;
		$db = get_or_create_db();
		
		request('task','withdraw_user',['project_id'=>$project_id,'user_id'=>$user_id]);
		
		$query = $db->prepare('DELETE FROM projects_users WHERE project_id = :pid AND user_id = :uid');
		assert($query->execute([':pid'=>$project_id,':uid'=>$user_id]),'Was not able to remove user from project!');
		
		info('User has been removed from project.');
	}
?>
