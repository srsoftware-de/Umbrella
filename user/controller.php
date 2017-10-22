<?php
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
				if ($redirect) $redirect.='?token='.$token;
				if (!$redirect && $user['id'] == 1) $redirect='index';
				if (!$redirect)	$redirect = getUrl('task');
				if (!$redirect)	$redirect = $user['id'].'/view';
				redirect($redirect);
			}
		}
		error('The provided username/password combination is not valid!');
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
			foreach ($rows as $row){
				$url = $row['domain'].'revoke?token='.$token;
				print_r($url);
				file_get_contents($url);
			}
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

	function add_user($db,$login,$pass){
		$hash = sha1($pass); // TODO: better hashing

		$query = $db->prepare('SELECT count(*) AS count FROM users WHERE login = :login');
		assert($query->execute(array(':login'=>$login)),'Was not able to assure non-existance of user!');
		$results = $query->fetchAll(PDO::FETCH_ASSOC);
		assert($results[0]['count'] == 0,'User with this login name already existing!');
		$query = $db->prepare('INSERT INTO users (login, pass) VALUES (:login, :pass);');
		assert ($query->execute(array(':login'=>$login,':pass'=>$hash)),'Was not able to add user '.$login);
	}

	function get_or_create_db(){
		if (!file_exists('db')){
			assert(mkdir('db'),'Failed to create user/db directory!');
		}
		assert(is_writable('db'),'Directory user/db not writable!');
		if (!file_exists('db/users.db')){
			$db = new PDO('sqlite:db/users.db');
			$db->query('CREATE TABLE users (id INTEGER PRIMARY KEY, login VARCHAR(255) NOT NULL, pass VARCHAR(255) NOT NULL);');
			$db->query('CREATE TABLE tokens (user_id INT NOT NULL PRIMARY KEY, token VARCHAR(255), expiration INTEGER NOT NULL)');
			$db->query('CREATE TABLE token_uses (token VARCHAR(255), domain TEXT);');
			add_user($db,'admin','admin');
		} else {
			$db = new PDO('sqlite:db/users.db');
		}
		return $db;
	}

	function get_userlist($ids = null,$include_passwords = false){
		$db = get_or_create_db();
		$columns = array('id', 'login');
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

	function alter_password($user,$new_pass){
		$db = get_or_create_db();
		$hash = sha1($new_pass); // TODO: better hashing
		$query = $db->prepare('UPDATE users SET pass = :pass WHERE id = :id;');
		assert ($query->execute(array(':pass'=>$hash,':id'=>$user['id'])),'Was not able to update user '.$user['login']);

	}

	function require_user_login(){
		global $services,$user;
		if ($_SESSION['token'] === null) redirect(getUrl('user','login?returnTo='.location()));

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
	}
?>
