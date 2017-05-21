<?php
	function perform_login($user = null, $pass = null){
		assert($user !== null && $pass !== null,'Missing username or password!');
		$db = get_or_create_db();
		$query = $db->prepare('SELECT * FROM users WHERE login = :user;');
		assert($query->execute(array(':user'=>$user)),'Was not able to request users from database!');
		$results = $query->fetchAll(PDO::FETCH_ASSOC);
		foreach ($results as $entry){
			$hash = $entry['pass'];
			if (sha1($pass) == $hash){
				return_token($entry);
			}
		}
		error('The provided username/password combination is not valid!'.$hash);
	}

	function add_user($db,$login,$pass){
		$hash = sha1($pass); // TODO: better hashing
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
			add_user($db,'admin','admin');
		} else {
			$db = new PDO('sqlite:db/users.db');
		}
		return $db;
	}
?>
