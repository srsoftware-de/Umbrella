<?php

	const FILE_PERMISSION_OWNER = 1;
	const FILE_PERMISSION_READ  = 2;
	const FILE_PERMISSION_WRITE = 3;
	const RECURSIVE = true;
	const STORAGE = '/.storage';
	const DS = '/';

	function get_or_create_db(){
		if (!file_exists('db'))assert(mkdir('db'),'Failed to create file/db directory!');
		assert(is_writable('db'),'Directory file'.DS.'db not writable!');
		return new PDO('sqlite:db'.DS.'files.db');
	}

	function base_dir(){
		return getcwd().STORAGE;
	}

	function list_entries($path = null){
		global $user;
		if ($path === null) $path = DS.'user'.$user->id;
		if ($path[0] != DS) $path = DS.$path;
		$base_dir = base_dir();
		$dir = base_dir().$path;
		$entries = scandir($dir);
		$dirs = [];
		$files = [];
		foreach ($entries as $entry){
			if (in_array($entry,['.','..'])) continue;
			if (is_dir($dir.DS.$entry)) {
				$dirs[] = $entry;
			} else $files[] = $entry;
		}
		return ['dirs'=>$dirs,'files'=>$files];
	}

	function get_absolute_path($filename = null){
		global $user;
		if ($filename === null || $filename == '') {
			error('No filename passed to download!');
			return null;
		}
		if ($filename[0] != DS) $filename = DS.$filename;
		if (strpos($filename,DS.'user'.$user->id)!==false) return base_dir().$filename;
		// TODO: implement hook to access shared files of other users
		error('You are not allowed to access ?',$filename);
		return null;
	}


	function add_file($file_data){
		global $user;
		$dir = param('dir');
		if (!$dir) $dir = DS.'user'.$user->id;	
		
		$filename = base_dir().DS.$dir.DS.$file_data['name'];
		if (strpos($filename,DS.'user'.$user->id)===false) return t('You are not allowed to write to ?',dirname($filename));
		
		if (file_exists($filename)) return 'A file "'.$filename.'" already exists!';
		$directory = dirname($filename);
		if (file_exists($directory)){
			if (!is_dir($directory)) return t('? exists, but is not a directory!',$directory);
		} else {
			if (!mkdir($directory,0777,RECURSIVE)) return t('Was not able to create folder ?!',$directory);
		}
		
		if (!rename($file_data['tmp_name'], $filename)) return t('Was not able to move file to ?!',$directory);
		
		return true;
	}	

	function delete_file($filename){
		
		assert(unlink($filename),t('Was not able to physically unlink file "?"',$filename));	
	}	
?>
