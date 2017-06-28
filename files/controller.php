<?php

	const FILE_PERMISSION_OWNER = 1;
	const FILE_PERMISSION_READ  = 2;
	const FILE_PERMISSION_WRITE = 3;
	const RECURSIVE = true;
	const STORAGE = '/.storage';	
	
	$FILE_PERMISSIONS = array(FILE_PERMISSION_OWNER=>'owner',FILE_PERMISSION_READ=>'read',FILE_PERMISSION_WRITE=>'write');	

	function get_or_create_db(){
		if (!file_exists('db')){
			assert(mkdir('db'),'Failed to create file/db directory!');
		}
		assert(is_writable('db'),'Directory file/db not writable!');
		if (!file_exists('db/files.db')){
			$db = new PDO('sqlite:db/files.db');
			$db->query('CREATE TABLE files (hash VARCHAR(50) PRIMARY KEY, type VARCHAR(255), path TEXT NOT NULL);');
			$db->query('CREATE TABLE files_users (hash VARCHAR(50) NOT NULL, user_id INT NOT NULL, permissions INT DEFAULT 0, PRIMARY KEY(hash, user_id));');
		} else {
			$db = new PDO('sqlite:db/files.db');
		}
		return $db;
	}
	
	function list_files($user_id){
		assert(is_numeric($user_id),'No valid user id passed to list files!');
		$db = get_or_create_db();
		$query = $db->prepare('SELECT * FROM files WHERE hash IN (SELECT hash FROM files_users WHERE user_id = :uid) ORDER BY path');
		assert($query->execute(array(':uid'=>$user_id)),'Was not able to read users files');
		$files = $query->fetchAll(INDEX_FETCH);
		return $files;		
	}

	function load_file($hash){
		$db = get_or_create_db();
		$query = $db->prepare('SELECT * FROM files WHERE hash = :hash');
		assert($query->execute(array(':hash'=>$hash)),'Was not able to read file');
		$results = $query->fetchAll(PDO::FETCH_ASSOC);
		if (empty($results)) return null;
		return $results[0];		
	}
	
	function add_file($file_data,$folder='',$user_id){
		assert(is_numeric($user_id),'No valid user id passed to add_file!');
		
		$hash = sha1_file($file_data['tmp_name']);
		$db = get_or_create_db();
		
		$query = $db->prepare('SELECT * FROM files WHERE hash = :hash');
		assert($query->execute(array(':hash'=>$hash)),'Was not able to query files table for file hash!');
		$items = $query->fetchAll(INDEX_FETCH);
		if (!empty($items)) {
			$item1 = reset($items);
			return 'This file has already been uploaded as "'.$item1['path'].'"!';
		}
		
		$storage = getcwd().STORAGE;
		if (strpos($folder, '/') !== 0) $folder = '/'.$folder;
		$filename = $folder.'/'.$file_data['name'];
		
		if (file_exists($storage.$filename)) return 'A file "'.$filename.'" already exists!';
		if (file_exists($storage.$folder)){
			if (!is_dir($storage.$folder)) return $folder.' exists, but is not a directory!';
		} else {
			if (!mkdir($storage.$folder,0777,RECURSIVE)) return 'Was not able to create folder '.$folder.'!';
		}
		
		
		if (!move_uploaded_file($file_data['tmp_name'], $storage.$filename)) return 'Was not able to move file to '.$folder.'!';
		
		$query = $db->prepare('INSERT INTO files (hash, type, path) VALUES (:hash, :type, :path)');
		assert($query->execute(array(':hash'=>$hash,':type'=>$file_data['type'],':path'=>$filename)),'Was not able to store file '.$filename);
		
		$query = $db->prepare('INSERT INTO files_users (hash, user_id, permissions) VALUES (:hash, :user, :perm)');
		assert($query->execute(array(':hash'=>$hash, ':user'=>$user_id,':perm'=>FILE_PERMISSION_OWNER)),'Was not able to set permissions on file');
		return true;
	}
	
	function load_users(&$files){
		$db = get_or_create_db();
		$first_entry = reset($files);		
		$is_array = isset($first_entry['path']);
		$qMarks = '?';
		$hashes = array($files['hash']);	
		if ($is_array){
			$hashes = array_keys($files);		
			$qMarks = str_repeat('?,', count($hashes) - 1) . '?';
		}
		$query = $db->prepare('SELECT * FROM files_users WHERE hash IN ('.$qMarks.')');
		assert($query->execute($hashes),'Was not able to read file permissions!');		
		$permissions = $query->fetchAll(PDO::FETCH_ASSOC);
		$user_ids = array();
		foreach ($permissions as $permission){
			$user_ids[$permission['user_id']]=null;
		}
		$users = request('user', 'list?ids='.implode(',', array_keys($user_ids)));
		foreach ($permissions as $permission){
			$hash = $permission['hash'];
			$uid = $permission['user_id'];
			$user = $users[$uid];
			if ($is_array){
				$files[$hash]['users'][$uid] = $user;
				$files[$hash]['users'][$uid]['permissions'] = $permission['permissions'];
			} else {
				$files['users'][$uid] = $user;
				$files['users'][$uid]['permissions'] = $permission['permissions'];
			}
		}
	}
	
	function assign_user_to_file($uid = null,$file_hash = null){
		assert(is_numeric($uid),'No valid uid passed to assign_user_to_file!');
		assert($file_hash !== null,'No valid file hash passed to assign_user_to_file!');
		$db = get_or_create_db();
		$query = $db->prepare('INSERT OR IGNORE INTO files_users (hash, user_id, permissions) VALUES (:hash, :user, :perm)');
		assert($query->execute(array('hash'=>$file_hash,':user'=>$uid,':perm'=>FILE_PERMISSION_READ)),'Was not able to assign file to user');
	}
	
	function delete_file($file_hash = null){
		assert($file_hash !== null,'No valid file hash passed to assign_user_to_file!');
		
		$file = load_file($file_hash);
		$handle = getcwd().STORAGE.$file['path'];
		
		$db = get_or_create_db();
		$query = $db->prepare('DELETE FROM files WHERE hash = :hash');
		assert($query->execute(array('hash'=>$file_hash)),'Was not able to delete file "'.$file['path'].'"');

		$query = $db->prepare('DELETE FROM files_users WHERE hash = :hash');
		assert($query->execute(array('hash'=>$file_hash)),'Was not able to delete user associations of file "'.$file['path'].'"');
		
		assert(unlink($handle),'Was not able to physically unlink file "'.$file['path'].'"');	
	}
?>
