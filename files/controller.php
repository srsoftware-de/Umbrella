<?php

	const FILE_PERMISSION_OWNER = 1;
	const FILE_PERMISSION_READ  = 2;
	const FILE_PERMISSION_WRITE = 3;
	const RECURSIVE = true;
	const STORAGE = '/.storage';	

	function get_or_create_db(){
		if (!file_exists('db'))assert(mkdir('db'),'Failed to create file/db directory!');
		assert(is_writable('db'),'Directory file/db not writable!');
		return new PDO('sqlite:db/files.db');
	}

	function base_dir(){
		return getcwd().STORAGE;
	}

	function list_entries($path = null){
		global $user;
		if ($path === null) $path = '/user'.$user->id;
		if ($path[0] != '/') $path = '/'.$path;
		$base_dir = base_dir();
		$dir = base_dir().$path;
		$entries = scandir($dir);
		$dirs = [];
		$files = [];
		foreach ($entries as $entry){
			if (in_array($entry,['.','..'])) continue;
			if (is_dir($dir.'/'.$entry)) {
				$dirs[] = $entry;
			} else $files[] = $entry;
		}
		return ['dirs'=>$dirs,'files'=>$files];
	}

	function get_absolute_path($filename = null){
		global $user;
		if ($filename === null || $filename == '') {
			error('No filename passed to download!');
			return false;
		}
		if ($filename[0] != '/') $filename = '/'.$filename;
		if (strpos($filename,'/user'.$user->id)!==false) return base_dir().$filename;
		// TODO: implement hook to access shared files of other users
		return null;
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
