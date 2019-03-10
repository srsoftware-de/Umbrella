<?php include '../bootstrap.php';

	const FILE_PERMISSION_OWNER = 1;
	const FILE_PERMISSION_READ  = 2;
	const FILE_PERMISSION_WRITE = 3;
	const RECURSIVE = true;
	const STORAGE = '/.storage';
	const MODULE = 'Files';

	$title = 'Umbrella File Management';
	static $projects = null;
	static $companies = null;

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

	function user_dir(){
		global $user;
		return 'user'.DS.$user->id;
	}

	function path_elements($path,$index = null){
		$elements = explode(DS, $path);
		return $index!==null ? $elements[$index] : $elements;
	}

	function projects(){
		global $projects;
		if (!$projects) $projects = request('project','json');
		return $projects;
	}

	function companies(){
		global $companies;
		if (!$companies) $companies = request('company','json');
		return $companies;
	}

	function access_granted($relative_path){
		if (in_array($relative_path, [user_dir(), 'project', 'company'])) return true;

		switch (path_elements($relative_path,0)){
			case 'user':
				if (strpos($relative_path,user_dir().DS)===0) return true; // if path startes with user/<id>/
				break;
			case 'project':
				$project_id = path_elements($relative_path,1);
				if (array_key_exists($project_id, projects())) return true;
				break;
			case 'company':
				$company_id = path_elements($relative_path,1);
				if (array_key_exists($company_id, companies())) return true;
				break;
		}

		$shared_files = shared_files_list();
		foreach ($shared_files as $entry) {
			if ($entry['file'] == $relative_path) return true;
		}

		return false;
	}

	function list_entries($relative_path = null){
		global $services;
		if ($relative_path == null){
			global $user;
			$result = [
				'dirs' => [
					t('private files') => 'user'.DS.$user->id
				],
				'files' => []
			];
			if (isset($services['company'])) $result['dirs'][t('Companies')] = 'company';
			if (isset($services['project'])) $result['dirs'][t('Projects')] = 'project';
			return $result;
		}

		if (access_granted($relative_path)){
			$dirs = ['..'=>dirname($relative_path)];
			$files = [];

			switch ($relative_path){

				case ('project'):
					foreach (projects() as $project) $dirs[$project['name']] = 'project'.DS.$project['id'];
					break;
				case 'company':
					foreach (companies() as $company) $dirs[$company['name']] = 'company'.DS.$company['id'];
					break;
				default:
					$absolute_path = base_dir().DS.$relative_path;
					$entries = file_exists($absolute_path) ? scandir($absolute_path) : [];
					foreach ($entries as $entry){
						if (in_array($entry,['.','..'])) continue;
						if (is_dir($absolute_path.DS.$entry)) {
							$dirs[$entry] = $relative_path.DS.$entry;
						} else $files[$entry] = $relative_path.DS.$entry;
					}
			}
			return ['dirs'=>$dirs,'files'=>$files];
		}
		return null;
	}


	function add_file($file_data){
		global $user;
		$dir = param('dir');
		if (in_array($dir, ['project','company'])) return t('You are not allowed to add files to "?"!',$dir);
		$filename = base_dir().DS.$dir.DS.$file_data['name'];
		if (!$filename) return null;

		if (file_exists($filename)) return 'A file "'.$filename.'" already exists!';
		$directory = dirname($filename);
		if (file_exists($directory)){
			if (!is_dir($directory)) return t('? exists, but is not a directory!',$directory);
		} else {
			if (!mkdir($directory,0777,RECURSIVE)) return t('Was not able to create folder ?!',$directory);
		}

		$dir_parts = explode(DS, $dir);
		$base_folder = array_shift($dir_parts);

		$users = false;
		if ($base_folder == 'project'){
			$project_id = array_shift($dir_parts);
			$project_user_ids = request('project','json',['ids'=>$project_id,'users'=>'only']);
			$users = request('user','json',['ids'=>array_keys($project_user_ids)]);
			$subject = t('? uploaded a file to your project',$user->login);
		} elseif ($base_folder == 'company'){
			$company_id = array_shift($dir_parts);
			$company = request('company','json',['ids'=>$company_id,'single'=>true,'users'=>true],1);
			$users = request('user','json',['ids'=>$company['users']]);
			$subject = t('? uploaded a file for your company',$user->login);
		}

		if (!rename($file_data['tmp_name'], $filename)) return t('Was not able to move file to ?!',$directory);

		if ($users && param('notify')){
			$sender = $user->email;
			$url = getUrl('files','?path='.$dir);
			$text = t('The file "?" has been uploaded to ?.',[$file_data['name'],$url]);
			foreach ($users as $u){
				$reciever = $u['email'];
				if ($sender == $reciever) continue;
				send_mail($sender, $reciever, $subject, $text);
			}
			info('Notifications were sent.');
		}

		if (strtolower(substr($filename,-4))=='.dia'){
			mkdir($directory.DS.'.dia');
			$target = $directory.DS.'.dia'.DS.basename($filename).'.png';
			$out = [];
			$return_code = 0;
			exec('dia -e '.$target.' '.$filename,$out,$return_code);
			if ($return_code === 0) info('Created file ?',$target);

		}
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
		if (access_granted($dirname) && mkdir(base_dir().DS.$dirname,0777,RECURSIVE)) return $dirname;
		return false;
	}



	function get_shares($filename){
		$absolute_path = base_dir().DS.$filename;

		$db = get_or_create_db();

		$query = $db->prepare('SELECT user_id,file FROM file_shares WHERE file = :file');
		assert($query->execute([':file'=>$filename]),'Was no able to query file list.');
		$rows = $query->fetchAll(INDEX_FETCH);
		return $rows;
	}

	function shared_files_list($filename = null){
		global $user;
		$db = get_or_create_db();

		$sql = 'SELECT file FROM file_shares WHERE user_id = :uid';
		$args = [':uid'=>$user->id];
		if ($filename !== null){
			$sql .= ' AND file = :file';
			$args[':file'] = $filename;
		}
		$query = $db->prepare($sql);
		assert($query->execute($args),'Was no able to query file list.');
		return $query->fetchAll(PDO::FETCH_ASSOC);
	}

	function shared_files($filename = null){
		$result = array();
		foreach (shared_files_list($filename) as $row){
			$filename = $row['file'];
			$parts = explode(DS, $filename);
			$pointer = &$result;
			while ($part = array_shift($parts)){

				if (!isset($pointer[$part])){
					$pointer[$part] = [];
					if (count($parts) == 1){
						$pointer[$part][array_shift($parts)] = $filename;
					}
				}
				$pointer = &$pointer[$part];
			}
		}
		return $result;
	}

	function share_file($filename = null,$user_id_and_email = null,$send_mail = null){
		global $user;

		$parts = explode('|', $user_id_and_email,2);
		$user_id = $parts[0];
		$reciever = $parts[1];
		$sender = $user->email;

		assert(is_string($filename),'No filename given!');
		assert(is_numeric($user_id),'No user selected!');
		$db = get_or_create_db();

		$query = $db->prepare('INSERT INTO file_shares (file, user_id) VALUES (:file, :uid);');
		assert($query->execute([':file'=>$filename,':uid'=>$user_id]),'Was not able to save file setting.');
		info('File "?" has been shared.',$filename);

		$url = getUrl('files','shared?path='.urldecode(dirname($filename)));
		if ($send_mail == 'on')	send_mail($sender, $reciever, t('? shared a file with you',$user->login),t('You now have access to the file "?". Go to ? to download it.',[basename($filename),$url]));
		redirect(getUrl('files','share?file='.urlencode($filename)));
	}

	function unshare_file($filename = null,$user_id = null){
		assert(is_string($filename),'No filename given!');
		assert(is_numeric($user_id),'No user selected!');
		$db = get_or_create_db();

		$query = $db->prepare('DELETE FROM file_shares WHERE file = :file AND user_id = :uid;');
		debug($query);
		assert($query->execute([':file'=>$filename,':uid'=>$user_id]),'Was not able to save file setting.');
		info('File "?" has been unshared.',$filename);
		redirect(getUrl('files','share?file='.urlencode($filename)));
	}

	function load_connected_users(){
		global $services;
		$user_ids = [];
		if (isset($services['company'])) {
			$c_users = request('company','json',['users'=>'only']);
			foreach ($c_users as $uid) $user_ids[$uid] = true;
		}
		if (isset($services['project'])) {
			$p_users = array_keys(request('project','json',['users'=>'only']));
			foreach ($p_users as $uid) $user_ids[$uid] = true;
		}
		return request('user','json',['ids'=>array_keys($user_ids)]);
	}

	function renameFile($currentname = null,$newname = null){
		if ($currentname === null || trim($currentname == '')) return error('Rename called, but no source file given!');
		if ($newname === null || trim($newname == '')) return error('Rename called, but no new name given!');
		$origin = base_dir().DS.$currentname;
		$dir = dirname($origin);
		$target = $dir.DS.$newname;

		if (!rename($origin,$target)) {
			error('Was not able to rename file!');
			return false;
		}

		$new_local = dirname($currentname).DS.$newname;

		$db = get_or_create_db();
		$query = $db->prepare('UPDATE file_shares SET file = replace(file, :currentname, :newname) WHERE file LIKE :search');
		$query->execute([':currentname'=>$currentname.DS,':newname'=>$new_local.DS,':search'=>$currentname.DS.'%']);
		$query = $db->prepare('UPDATE file_shares SET file = :newname WHERE file = :currentname');
		$query->execute([':currentname'=>$currentname,':newname'=>$new_local]);
		//debug(['current name'=>$currentname,'origin'=>$origin,'dir'=>$dir,'new name'=>$newname,'new local'=>$new_local,'target'=>$target],1);
		return true;
	}

	function is_image($name){
		$parts = explode('.',$name);
		$extension = strtolower(array_pop($parts));
		return in_array($extension,['jpg','jpeg','gif','png','svg']);
	}
?>
