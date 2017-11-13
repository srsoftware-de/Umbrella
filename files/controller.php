<?php

	const FILE_PERMISSION_OWNER = 1;
	const FILE_PERMISSION_READ  = 2;
	const FILE_PERMISSION_WRITE = 3;
	const RECURSIVE = true;
	const STORAGE = '/.storage';
	const DS = '/';

	function get_or_create_db(){
		if (!file_exists('db')) assert(mkdir('db'),'Failed to create files'.DS.'db directory!');
		assert(is_writable('db'),'Directory files'.DS.'db not writable!');
		if (!file_exists('db'.DS.'files.db')){
			$db = new PDO('sqlite:db'.DS.'files.db');
			$db->query('CREATE TABLE file_shares (file VARCHAR(2048) NOT NULL, user_id INT NOT NULL, PRIMARY KEY(file, user_id));');
		} else {
			$db = new PDO('sqlite:db'.DS.'files.db');
		}
		return $db;
		
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
		if (!empty(shared_files(ltrim($filename,DS)))) return base_dir().$filename;
		
		
		error('You are not allowed to access ?',$filename);
		return null;
	}


	function add_file($file_data){
		global $user;
		$dir = param('dir');
		if (!$dir) $dir = 'user'.$user->id;	
		$filename = get_absolute_path($dir.DS.$file_data['name']);
		if (!$filename) return null;
		if (strpos($filename,'user'.$user->id)===false) return t('You are not allowed to write to ?',dirname($filename));
		
		if (file_exists($filename)) return 'A file "'.$filename.'" already exists!';
		$directory = dirname($filename);
		if (file_exists($directory)){
			if (!is_dir($directory)) return t('? exists, but is not a directory!',$directory);
		} else {
			if (!mkdir($directory,0777,RECURSIVE)) return t('Was not able to create folder ?!',$directory);
		}
		
		if (!rename($file_data['tmp_name'], $filename)) return t('Was not able to move file to ?!',$directory);
		
		return ['name'=>$file_data['name'],'absolute'=>$filename,'dir'=>$dir];
	}	

	function delete_file($filename){
		if (is_dir($filename)){
			assert(rmdir($filename),t('Was not able to remove directory ?',basename($filename)));
		} else {
			assert(unlink($filename),t('Was not able to physically unlink file "?"',basename($filename)));	
		}
	}

	function create_dir($dirname){
		global $user;
		$rel_par = param('dir','user'.$user->id);	
		$parent = get_absolute_path($rel_par);
		if (!$parent) return false;
		if (mkdir($parent.DS.$dirname)) return $rel_par.DS.$dirname;
		return false;
	}



	function get_shares($filename){
		$absolute_path = get_absolute_path($filename);
		if (!$absolute_path) return error('You are not allowed to access ?',$filename);
		$db = get_or_create_db();
		
		$query = $db->prepare('SELECT user_id FROM file_shares WHERE file = :file');
		assert($query->execute([':file'=>$filename]),'Was no able to query file list.');
		$rows = $query->fetchAll(PDO::FETCH_ASSOC);
		return $rows;
	}
	
	function shared_files($filename = null){
		global $user;
		//$absolute_path = get_absolute_path($filename);
		$db = get_or_create_db();
	
		$sql = 'SELECT file FROM file_shares WHERE user_id = :uid';
		$args = [':uid'=>$user->id];
		if ($filename !== null){
			$sql .= ' AND file = :file';
			$args[':file'] = $filename; 
		}
		$query = $db->prepare($sql);
		assert($query->execute($args),'Was no able to query file list.');
		$rows = $query->fetchAll(PDO::FETCH_ASSOC);		
		if ($filename !== null) return $rows; // used by download()
		$result = array();
		foreach ($rows as $row){
			$filename = $row['file'];
			$parts = explode(DS, $filename);
			$pointer = &$result;
			while ($part = array_shift($parts)){
				
				if (!isset($pointer[$part])){
					$pointer[$part] = [];
					if (count($parts) == 1){
						$pointer[$part][array_shift($parts)] = file;
					}
					
				}
				$pointer = &$pointer[$part];
				
			}
			
		}		
		return $result;
	}

	function share_file($filename = null,$user_id = null){
		assert(is_string($filename),'No filename given!');
		assert(is_numeric($user_id),'No user selected!');
		$db = get_or_create_db();
	
		$query = $db->prepare('INSERT INTO file_shares (file, user_id) VALUES (:file, :uid);');
		assert($query->execute([':file'=>$filename,':uid'=>$user_id]),'Was not able to save file setting.');
		redirect(getUrl('files','share?file='.urlencode($filename)));
	}	
	
	function unshare_file($filename = null,$user_id = null){
		assert(is_string($filename),'No filename given!');
		assert(is_numeric($user_id),'No user selected!');
		$db = get_or_create_db();
		
		$query = $db->prepare('DELETE FROM file_shares WHERE file = :file AND user_id = :uid;');
		debug($query);
		assert($query->execute([':file'=>$filename,':uid'=>$user_id]),'Was not able to save file setting.');
		redirect(getUrl('files','share?file='.urlencode($filename)));
	}
	
	function load_connected_users(){
		$projects = request('project','list');
		$project_ids = array_keys($projects);
		$project_users = request('project','user_list',['id'=>$project_ids]);
		$user_list = request('user','list');
		return array_intersect_key($user_list, $project_users);		
	}
	
	function renameFile($currentname = null,$newname = null){
		if ($currentname === null || trim($currentname == '')) return error('Rename called, but no source file given!');
		if ($newname === null || trim($newname == '')) return error('Rename called, but no new name given!');
		$origin = get_absolute_path($currentname);
		if ($origin){
			$dir = dirname($origin);
			$target = $dir.DS.$newname;
			if (!rename($origin,$target)) return error('Was not able to rename file!');
			redirect('index?path='.dirname($currentname));
		}
	}
?>
