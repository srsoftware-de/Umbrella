<?php

	$db_handle = null;

	function perform_login($login = null, $pass = null){
		assert($login !== null && $pass !== null,'Missing username or password!');
		$db = get_or_create_db();
		$query = $db->prepare('SELECT * FROM users WHERE login = :login;');
		assert($query->execute(array(':login'=>$login)),'Was not able to request users from database!');
		$results = $query->fetchAll(PDO::FETCH_ASSOC);
		foreach ($results as $user){
			if (sha1($pass) == $user['pass']){
				$token = getOrCreateToken($user);
				$redirect = param('returnTo');
				if (!$redirect && $user['id'] == 1) $redirect='index';
				if (!$redirect)	$redirect = getUrl('task');
				if (!$redirect)	$redirect = $user['id'].'/view';
				redirect($redirect);
			}
		}
		sleep(10);
		error('The provided username/password combination is not valid!');
	}
	
	function perform_id_login($id){
		$db = get_or_create_db();
		$query = $db->prepare('SELECT * FROM users WHERE id = :id;');
		assert($query->execute(array(':id'=>$id)),'Was not able to request users from database!');
		$results = $query->fetchAll(PDO::FETCH_ASSOC);
		foreach ($results as $user){
			$token = getOrCreateToken($user);
			$redirect = param('returnTo');
			if ($redirect) {
				if (strpos($redirect, '?') === false){
					$redirect.='?token='.$token;
				} else {
					$redirect.='&token='.$token;
				}
			}
			if (!$redirect && $user['id'] == 1) $redirect='index';
			if (!$redirect)	$redirect = getUrl('task');
			if (!$redirect)	$redirect = $user['id'].'/view';
			redirect($redirect);
			break;
		}
		error('No user found for id',$id);
	}

	function getOrCreateToken($user = null){
		assert(is_array($user) && !empty($user),'Parameter "user" null or empty!');
		$db = get_or_create_db();
		$query = $db->prepare('SELECT * FROM tokens WHERE user_id = :userid');
		assert($query->execute(array(':userid'=>$user['id'])),'Was not able to execute SELECT statement.');
		$results = $query->fetchAll(PDO::FETCH_ASSOC);
		$token = null;
		foreach ($results as $row){
			$token = $row['token'];
		}
		if ($token === null) $token = generateRandomString();
		$expiration = time()+3600; // now + one hour
		$query = $db->prepare('INSERT OR REPLACE INTO tokens (user_id, token, expiration) VALUES (:uid, :token, :expiration);');
		assert($query->execute(array(':uid'=>$user['id'],':token'=>$token,':expiration'=>$expiration)),'Was not able to update token expiration date!');
		$_SESSION['token'] = $token;
		return $token;
	}

	function user_revoke_token(){
		global $user;
		$token = $_SESSION['token'];
		unset($_SESSION['token']);
		$db = get_or_create_db();
		$query = $db->prepare('DELETE FROM tokens WHERE token = :token');
		assert($query->execute(array(':token'=>$token)),'Was not able to execute DELETE statement.');
		
		$query = $db->prepare('SELECT domain FROM token_uses WHERE token = :token;');
		if ($query->execute([':token'=>$token])){
			$rows = $query->fetchAll(PDO::FETCH_ASSOC);
			foreach ($rows as $row) file_get_contents($row['domain'].'?revoke='.$token);
		}
	}

	function generateRandomString(){
		return bin2hex(openssl_random_pseudo_bytes(40));
	}

	function load_user($id = null){
		assert($id !== null,'No user id passed to load_user!');
		assert(is_numeric($id),'Invalid user id passed to load_user!');
		$db = get_or_create_db();
		$query = $db->prepare('SELECT * FROM users WHERE id = :id');
		assert($query->execute(array(':id'=>$id)));
		$results = $query->fetchAll(PDO::FETCH_ASSOC);
		return objectFrom($results[0]);
	}

	function lock_user($id = null){
		assert($id !== null,'No user id passed to lock_user!');
		assert(is_numeric($id),'Invalid user id passed to lock_user!');
		$db = get_or_create_db();
		$query = $db->prepare('UPDATE users SET pass="" WHERE id = :id');
		debug($query);
		assert($query->execute(array(':id'=>$id)));
		$query = $db->prepare('DELETE FROM service_ids_users WHERE user_id = :id');
		debug($query);
		assert($query->execute(array(':id'=>$id)));
	}
	
	function user_exists($login){
		$db = get_or_create_db();
		
		$query = $db->prepare('SELECT count(*) AS count FROM users WHERE login = :login');
		assert($query->execute(array(':login'=>$login)),'Was not able to assure non-existance of user!');
		$results = $query->fetchAll(PDO::FETCH_ASSOC);
		return $results[0]['count'] > 0;
		
		
	}

	function add_user($login,$pass){
		
		$db = get_or_create_db();
		
		if (user_exists($login)) {
			error('User with this login name already existing!');
			return false;
		}
		
		$hash = sha1($pass); // TODO: better hashing		
		
		$query = $db->prepare('INSERT INTO users (login, pass) VALUES (:login, :pass);');
		assert ($query->execute(array(':login'=>$login,':pass'=>$hash)),'Was not able to add user '.$login);
		return true;
	}

	function get_or_create_db(){
		global $db_handle;
		if ($db_handle !== null) return $db_handle;
		if (!file_exists('db')){
			assert(mkdir('db'),'Failed to create user/db directory!');
		}
		assert(is_writable('db'),'Directory user/db not writable!');
		if (!file_exists('db/users.db')){
			$db_handle = new PDO('sqlite:db/users.db');
			$db_handle->query('CREATE TABLE users (id INTEGER PRIMARY KEY, login VARCHAR(255) NOT NULL, pass VARCHAR(255) NOT NULL, email VARCHAR(255), theme VARCHAR(50));');
			$db_handle->query('CREATE TABLE tokens (user_id INT NOT NULL PRIMARY KEY, token VARCHAR(255), expiration INTEGER NOT NULL)');
			$db_handle->query('CREATE TABLE token_uses (token VARCHAR(255), domain TEXT);');
			$db_handle->query('CREATE TABLE login_services (name VARCHAR(255), url TEXT, client_id VARCHAR(255), client_secret VARCHAR(255), user_info_field VARCHAR(255), PRIMARY KEY (name));');
			$db_handle->query('CREATE TABLE service_ids_users (service_id VARCHAR(255) NOT NULL PRIMARY KEY, user_id INT NOT NULL);');
			add_user('admin','admin');
		} else {
			$db_handle = new PDO('sqlite:db/users.db');
		}
		return $db_handle;
	}

	function get_userlist($ids = null,$include_passwords = false){
		$db = get_or_create_db();
		$columns = array('id','id', 'login', 'email');
		if ($include_passwords) $columns[]='pass';
		$sql = 'SELECT '.implode(', ', $columns).' FROM users';
		$args = array();

		if (is_array($ids) && !empty($ids)){
			$qMarks = str_repeat('?,', count($ids) - 1) . '?';
			$sql .= ' WHERE id IN ('.$qMarks.')';
			$args = $ids;
		}
		$query = $db->prepare($sql);
		assert($query->execute($args),'Was not able to request user list!');
		$results = $query->fetchAll(INDEX_FETCH);
		return $results;
	}

	function login_services(){
		$db = get_or_create_db();
		$query = $db->prepare('SELECT * FROM login_services');
		assert($query->execute(),'Was not able to load login service list.');
		return $query->fetchAll(INDEX_FETCH);
	}

	function alter_password($user,$new_pass){
		$db = get_or_create_db();
		$hash = sha1($new_pass); // TODO: better hashing
		$query = $db->prepare('UPDATE users SET pass = :pass WHERE id = :id;');
		assert ($query->execute(array(':pass'=>$hash,':id'=>$user->id)),'Was not able to update user '.$user->login);
		info('Your password has been changed.');
	}
	function update_user($user){	
		if (empty($user->dirty)) return false;
		$db = get_or_create_db();
		$sql = 'UPDATE users SET ';
		$args = [];
		foreach ($user->dirty as $field){
			if (in_array($field,['id','pass'])) continue;
			$args[':'.$field] = $user->{$field};
			$sql .= $field.' = :'.$field.', ';
		}
		$sql=rtrim($sql,', ').' WHERE id = :id';
		$args[':id'] = $user->id;
		$query = $db->prepare($sql);
		assert ($query->execute($args),'Was not able to update user '.$user->login);
		info('User data has been updated.');
		warn('If you changed your theme, you will have to log off an in again.');
		return true;
	}

	function require_user_login(){
		global $services,$user,$theme;
		if (!isset($_SESSION['token']) || $_SESSION['token'] === null) redirect(getUrl('user','login?returnTo='.location()));

		$db = get_or_create_db();

		$query = $db->prepare('SELECT * FROM tokens WHERE token = :token;');
		$params = array(':token' => $_SESSION['token']);
		assert($query->execute($params),'Was not able to request token table.');
		$rows = $query->fetchAll(PDO::FETCH_ASSOC);
		$time = time();
		$user_id = null;

		$query = $db->prepare('DELETE FROM tokens WHERE token = :token;');
		foreach ($rows as $index => $row){
			if ($row['expiration'] > $time){
				$user_id = $row['user_id']; // read user data
			} else 	$query->execute([':token'=>$row['token']]); // drop expired token
		}
		$user = ($user_id === null) ? null : load_user($user_id);
	        if (isset($user->theme)) $theme = $user->theme;
	}
	
	function get_login_services($name = null){
		$db = get_or_create_db();
		
		$sql = 'SELECT * FROM login_services';
		$args = [];
		if ($name) {
			$sql .= ' WHERE name = :name';
			$args[':name'] = $name;
		}
		$query = $db->prepare($sql);
		assert($query->execute($args),'Was not able to read login services list.');
		$rows = $query->fetchAll(INDEX_FETCH);
		if ($name) return $rows[$name];
		return $rows;
	}
	
	function assign_user_service($foreign_id){
		global $user;
		$db = get_or_create_db();
		
		$query = $db->prepare('INSERT INTO service_ids_users (service_id, user_id) VALUES (:service, :user);');
		assert($query->execute([':service'=>$foreign_id,':user'=>$user->id]),t('Was not able to assign service id (?) with your user account!',$foreign_id));
		redirect('index');
	}
	
	function deassign_service($foreign_id){
		$db = get_or_create_db();		
		$query = $db->prepare('DELETE FROM service_ids_users WHERE service_id = :service;');
		assert($query->execute([':service'=>$foreign_id]),t('Was not able to de-assign service id (?) from your user account!',$foreign_id));
		redirect('index');
	}
	
	function get_assigned_logins($foreign_id = null){
		global $user;
		$db = get_or_create_db();
		
		$sql = 'SELECT * FROM service_ids_users ';
		if ($foreign_id !== null) {			
			$sql .= 'WHERE service_id = :id';
			$args = [':id'=>$foreign_id];
		} else {
			$sql .= 'WHERE user_id = :id';
			$args = [':id'=>$user->id];
		}
		$query = $db->prepare($sql);
		assert($query->execute($args),'Was not able to read list of assigned logins.');
		$rows = $query->fetchAll(INDEX_FETCH);
		if ($foreign_id !== null) return $rows[$foreign_id];
		return $rows;
	}

	function get_themes(){
		$entries = scandir('common_templates/css');
		$results = [];
		foreach ($entries as $entry){
			if (in_array($entry,['.','..'])) continue;
			if (is_dir('common_templates/css/'.$entry)) $results[] = $entry;
		}
		return $results;
	}

	function add_login_service($login_service){
		assert(is_array($login_service),'Argument passed to user/controller::add_login_service is not an array!');
		$db = get_or_create_db();
		$args = [];
		foreach ($login_service as $k => $v){
			$args[':'.$k] = $v;
		}
		$query = $db->prepare('INSERT INTO login_services ('.implode(',',array_keys($login_service)).') VALUES ('.implode(',',array_keys($args)).')');
		assert($query->execute($args),'Was not able to add login service!');
	}

	function drop_login_service($name){
		$db = get_or_create_db();
		$query = $db->prepare('DELETE FROM login_services WHERE name = :name');
		assert($query->execute([':name'=>$name]),'Was not able to delete login_service "'.$name.'"!');
	}
?>
