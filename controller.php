<?php
	include '../bootstrap.php';

	const TASK_PERMISSION_OWNER = 1;
	const TASK_PERMISSION_READ_WRITE = 2;
	const TASK_PERMISSION_READ = 4;
	const MODULE = 'Task';
	$title = t('Umbrella Task Management');

	$TASK_PERMISSIONS = array(TASK_PERMISSION_OWNER=>'owner',TASK_PERMISSION_READ_WRITE=>'read + write',TASK_PERMISSION_READ=>'read only');

	function get_or_create_db(){
		$table_filename = 'tasks.db';
		if (!file_exists('db')) assert(mkdir('db'),'Failed to create task/db directory!');
		assert(is_writable('db'),'Directory task/db not writable!');
		if (!file_exists('db/'.$table_filename)){
			$db = new PDO('sqlite:db/'.$table_filename);

			$tables = [
					'tasks'=>Task::table(),
					'tasks_users'=>Task::users_table(),
					'task_dependencies'=>Task::dependencies_table()
			];

			foreach ($tables as $table => $fields){
				$sql = 'CREATE TABLE '.$table.' ( ';
				foreach ($fields as $field => $props){
					if ($field == 'UNIQUE'||$field == 'PRIMARY KEY') {
						$field .='('.implode(',',$props).')';
						$props = null;
					}
					$sql .= $field . ' ';
					if (is_array($props)){
						foreach ($props as $prop_k => $prop_v){
							switch (true){
								case $prop_k==='VARCHAR':
									$sql.= 'VARCHAR('.$prop_v.') '; break;
								case $prop_k==='DEFAULT':
									$sql.= 'DEFAULT '.($prop_v === null?'NULL ':'"'.$prop_v.'" '); break;
								case $prop_k==='KEY':
									assert($prop_v === 'PRIMARY','Non-primary keys not implemented in task/controller.php!');
									$sql.= 'PRIMARY KEY '; break;
								default:
									$sql .= $prop_v.' ';
							}
						}
						$sql .= ", ";
					} else $sql .= $props.", ";
				}
				$sql = str_replace([' ,',', )'],[',',')'],$sql.')');
				$query = $db->prepare($sql);
				assert($query->execute(),'Was not able to create '.$table.' table in '.$table_filename.'!');
			}
		} else {
			$db = new PDO('sqlite:db/'.$table_filename);
		}
		return $db;
	}

	function get_task_ids_of_user($ids = null){
		global $user;
		$sql = 'SELECT task_id FROM tasks_users WHERE ';
		$args = [];
		if (is_array($ids)){
			$qmarks = implode(',', array_fill(0, count($ids), '?'));
			$sql .= 'task_id IN ('.$qmarks.') AND ';
			$args = $ids;
		}
		$sql .= 'user_id = ?';
		$args[] = $user->id;
		$db = get_or_create_db();
		$query = $db->prepare($sql);
		assert($query->execute($args),'Was not able to read list of user-assigned tasks.');
		return array_keys($query->fetchAll(INDEX_FETCH));
	}

	function parseDownFormat(&$task){
		if (file_exists('../lib/parsedown/Parsedown.php')){
			include_once '../lib/parsedown/Parsedown.php';
			$task['description'] = Parsedown::instance()->parse($task['description']);
		} else {
			$task['description'] = str_replace("\n", "<br/>", $task['description']);
		}
	}

	function update_task_requirements($id,$required_task_ids){
		$db = get_or_create_db();

		if (empty($required_task_ids)) {
			$query = $db->prepare('DELETE FROM task_dependencies WHERE task_id = :tid');
			$query->execute([':tid'=>$id]);
			return;
		}
		$required_task_ids = array_keys($required_task_ids);

		$qmarks = implode(',', array_fill(0, count($required_task_ids), '?'));
		$args = $required_task_ids;
		$args[] = $id;

		$query = $db->prepare('DELETE FROM task_dependencies WHERE required_task_id NOT IN ('.$qmarks.') AND task_id = ?');
		$query->execute($args);
		$query = $db->prepare('INSERT INTO task_dependencies (task_id, required_task_id) VALUES (:id, :req);');
		foreach ($required_task_ids as $rid)$query->execute([':id'=>$id,':req'=>$rid]);
	}

	function update_task_states($db){
		$date = '"'.date('Y-m-d').'"';
		$db->exec('UPDATE tasks SET status = '.TASK_STATUS_OPEN.' WHERE status = '.TASK_STATUS_PENDING.' AND start_date != "" AND start_date <= '.$date);
		$db->exec('UPDATE tasks SET status = '.TASK_STATUS_OPEN.' WHERE status = '.TASK_STATUS_PENDING.' AND due_date != "" AND due_date <= '.$date);
		$db->exec('UPDATE tasks SET status = '.TASK_STATUS_PENDING.' WHERE status = '.TASK_STATUS_OPEN.' AND start_date != "" AND start_date > '.$date);
	}



	function load_tasks($options = array()){
		global $user;

		$db = get_or_create_db();
		update_task_states($db);

		$ids_only = isset($options['ids_only']) && $options['ids_only'];

		$sql = 'SELECT id';
		$where = [];
		$args = [];

		if (!$ids_only) { // if we request more than the task_ids: limit task list to user's tasks
			$sql .= ',*';
			$where[] = 'id IN (SELECT task_id FROM tasks_users WHERE user_id = ?)';
			$args[] = $user->id;
		}

		$sql .= ' FROM tasks';

		$single = false;
		if (isset($options['ids'])){
			$ids = $options['ids'];
			if (!is_array($ids) && $ids = [$ids]) $single = true;
			$qMarks = str_repeat('?,', count($ids)-1).'?';
			$where[] = 'id IN ('.$qMarks.')';
			$args = array_merge($args, $ids);
		}

		if (isset($options['project_ids'])){
			$ids = $options['project_ids'];
			if (!is_array($ids)) $ids = [$ids];
			$qMarks = str_repeat('?,', count($ids)-1).'?';
			$where[] = 'project_id IN ('.$qMarks.')';
			$args = array_merge($args, $ids);
		}

		if (isset($options['key'])){
			$key = '%'.$options['key'].'%';
			$where[] = '(name LIKE ? OR description LIKE ?)';
			$args = array_merge($args, [$key,$key]);
		}

		if (!empty($where)) $sql .= ' WHERE '.implode(' AND ', $where);

		if (!isset($options['order'])) $options['order'] = 'due_date';
		$MAX_DATE = "'9999-99-99'";
		switch ($options['order']){
			case 'due_date':
				$sql .= ' ORDER BY (CASE due_date WHEN "" THEN '.$MAX_DATE.' ELSE IFNULL(due_date,'.$MAX_DATE.') END), status COLLATE NOCASE';
				break;
			case 'name':
				$sql .= ' ORDER BY name COLLATE NOCASE';
				break;
			case 'project_id':
				$sql .= ' ORDER BY project_id, status, due_date COLLATE NOCASE';
				break;
			case 'parent_task_id':
				$sql .= ' ORDER BY project_id, parent_task_id, status, due_date COLLATE NOCASE';
				break;
			case 'start_date':
				$sql .= ' ORDER BY (CASE start_date WHEN "" THEN '.$MAX_DATE.' ELSE IFNULL(start_date,'.$MAX_DATE.') END), status COLLATE NOCASE';
				break;
			case 'status':
				$sql .= ' ORDER BY status, due_date COLLATE NOCASE';
				break;
		}

		$query = $db->prepare($sql);
		assert($query->execute($args),'Was not able to load tasks!');
		$rows = $query->fetchAll(INDEX_FETCH);
		if ($single) return reset($rows);
		return $rows;
	}

	function setTags($name,$task_id){
		if ($raw_tags = param('tags')){
			$raw_tags = explode(' ', str_replace(',',' ',$raw_tags));
			$tags = [];
			foreach ($raw_tags as $tag){
				if (trim($tag) != '') $tags[]=$tag;
			}
			$url = getUrl('task',$task_id.'/view');
			request('bookmark','add',['url'=>$url,'comment'=>t('Task: ?',$name),'tags'=>$tags]);
			return sha1($url);
		}
		return false;
	}

	function add_task($name,$description = null,$project_id = null,$parent_task_id = null, $start_date = null, $due_date = null, $users = []){
		global $user,$services;
		$db = get_or_create_db();
		assert($name !== null && trim($name) != '','Task name must not be empty or null!');
		assert(is_numeric($project_id),'Task must reference project!');
		$start_stamp = null;
		$status = TASK_STATUS_OPEN;
		if ($start_date !== null && $start_date != ''){
			$start_stamp = strtotime($start_date);
			assert($start_stamp !== false,'Start date is not a valid date!');
			if ($start_stamp > time()) $status = TASK_STATUS_PENDING;
		}
		if ($due_date !== null && $due_date != ''){
			$due_stamp = strtotime($due_date);
			assert($due_stamp !== false,'Due date is not a valid date!');
			if ($start_stamp && $start_stamp > $due_stamp){
				$start_date = $due_date;
				info('Start date adjusted to match due date!');
			}
		}
		$query = $db->prepare('INSERT INTO tasks (name, project_id, parent_task_id, description, status, est_time, start_date, due_date) VALUES (:name, :pid, :parent, :desc, :state, :est, :start, :due);');
		$args = [
			':name'=>$name,
			':pid'=>$project_id,
			':parent'=>$parent_task_id,
			':desc'=>$description,
			':state'=>$status,
			':est'=>param('est_time'),
			':start'=>$start_date,
			':due'=>$due_date
		];
		assert($query->execute($args),'Was not able to create new task entry in database');
		$task = [
			'id' => $db->lastInsertId(),
			'parent_task_id'=>$parent_task_id,
			'project_id'=>$project_id,
			'name' => $name,
			'description'=>$description,
			'status'=>TASK_STATUS_OPEN,
			'start_date'=>$start_date,
			'due_date'=>$due_date,
		];

		add_user_to_task($task,['id'=>$user->id,'email'=>$user->email,'login'=>$user->login,'permission'=>TASK_PERMISSION_OWNER]);
		$hash = isset($services['bookmark']) ? setTags($name,$task['id']) : false;

		foreach ($users as $id => $new_user) {
			if ($id == $user->id) continue;
			add_user_to_task($task,$new_user);
			if ($hash) request('bookmark','index',['share_user_id'=>$id,'share_url_hash'=>$hash,'notify'=>false]);
		}
		return $task;
	}

	function update_task($task){
		global $services,$user;

		// check
		assert(array_key_exists('id',$task),'$task does not contain "id"');
		assert(array_key_exists('name',$task)       && (trim($task['name']) != ''),'Task name must be set!');
		assert(array_key_exists('project_id',$task) && is_numeric($task['project_id']),'Task must reference project!');

		// calculate
		if (isset($task['start_date']) && trim($task['start_date']) != ''){
			$start_stamp = strtotime($task['start_date']);
			assert($start_stamp !== false,'Start date is not a valid date!');
		}
		if (isset($task['due_date']) && trim($task['due_date']) != ''){
			$due_stamp = strtotime($task['due_date']);
			assert($due_stamp !== false,'Due date is not a valid date!');
			if ($start_stamp && $start_stamp > $due_stamp){
				$task['start_date'] = $task['due_date'];
				warn('Start date adjusted to match due date!');
			}
		}

		// save
		$db = get_or_create_db();
		$query = $db->prepare('UPDATE tasks SET name = :name, project_id = :pid, parent_task_id = :parent, description = :desc, est_time = :est, start_date = :start, due_date = :due WHERE id = :id;');
		$args = [
			':id'=>$task['id'],
			':name'=>$task['name'],
			':pid'=>$task['project_id'],
			':parent'=>$task['parent_task_id'],
			':desc'=>$task['description'],
			':est'=>param('est_time'),
			':start'=>$task['start_date'],
			':due'=>$task['due_date']
		];
		assert($query->execute($args),'Was not able to alter task entry in database');

		$hash = isset($services['bookmark']) ? setTags($task['name'],$task['id']) : false;

		if (param('silent') != 'on'){ // notify task users
			$sender = $user->email;
			foreach ($task['users'] as $uid => $u){
				if ($uid == $user->id) continue;
				$reciever = $u['email'];
				$subject = t('? edited one of your tasks',$user->login);
				$text = t("The task \"?\" now has the following description:\n\n?\n\n",[$task['name'],$task['description']]).getUrl('task',$task['id'].'/view');
				send_mail($sender, $u['email'], $subject, $text);

				if ($hash) request('bookmark','index',['share_user_id'=>$uid,'share_url_hash'=>$hash,'notify'=>false]);
			}
		}
	}

	function set_task_state($task_id, $state){
		$db = get_or_create_db();
		assert(is_numeric($task_id),'invalid task id passed!');
		assert(is_numeric($state),'invalid state passed!');
		$query = $db->prepare('UPDATE tasks SET status = :state WHERE id = :id;');
		assert($query->execute(array(':state' => $state,':id'=>$task_id)),'Was not able to alter task state in database');
	}

	function load_requirements(&$task){
		$id = $task['id'];
		$db = get_or_create_db();
		$query = $db->prepare('SELECT * FROM tasks WHERE id IN (SELECT required_task_id FROM task_dependencies WHERE task_id = :id) ORDER BY status,name');
		assert($query->execute(array(':id'=>$id)),'Was not able to query requirements of '.$task['name']);
		$required_tasks = $query->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_UNIQUE|PDO::FETCH_ASSOC);
		if (!empty($required_tasks)) $task['requirements'] = $required_tasks;
	}

	function load_children(&$task,$levels = 0){
		$id = $task['id'];
		$db = get_or_create_db();
		$query = $db->prepare('SELECT * FROM tasks WHERE parent_task_id = :id ORDER BY name ASC');
		assert($query->execute(array(':id'=>$id)),'Was not able to query children of '.$task['name']);
		$child_tasks = $query->fetchAll(INDEX_FETCH);
		$child_time_sum = 0;
		foreach ($child_tasks as $id => &$child_task){
			$child_task['id'] = $id;
			$child_task['status_string'] = task_state([$child_task['status']]);
			if ($levels) load_children($child_task,$levels -1);
			$child_time_sum += $child_task['est_time'];
			if (isset($child_task['est_time_children'])) $child_time_sum += $child_task['est_time_children'];
		}
		if (!empty($child_tasks)){
			$task['children'] = $child_tasks;
			$task['est_time_children'] = $child_time_sum;
		}
	}

	function delete_task($task = null){
		assert(is_array($task),'No task passed to delete_task!');
		$db = get_or_create_db();
		$args = [':id'=>$task['id']];

		$query = $db->prepare('DELETE FROM tasks WHERE id = :id');
		assert($query->execute([':id'=>$task['id']]));

		$query = $db->prepare('DELETE FROM task_dependencies WHERE task_id = :id');
		assert($query->execute([':id'=>$task['id']]));

		$query = $db->prepare('DELETE FROM tasks_users WHERE task_id = :id');
		assert($query->execute([':id'=>$task['id']]));

		$args[':ptid']= $task['parent_task_id'];
		$query = $db->prepare('UPDATE tasks SET parent_task_id = :ptid WHERE parent_task_id = :id');
		assert($query->execute($args));
		info('Task has been deleted.');
	}

	function load_users(&$task,$project_users = null){
		$id = $task['id'];
		assert(is_numeric($id),'Invalid task id passed to load_users!');
		$db = get_or_create_db();
		$query = $db->prepare('SELECT * FROM tasks_users WHERE task_id = :id');
		assert($query->execute(array(':id'=>$id)));
		$results = $query->fetchAll(PDO::FETCH_ASSOC);

		$users = array();
		foreach ($results as $result){
			$user_id = $result['user_id'];
			$users[$user_id] = empty($project_users)?[]:$project_users[$user_id];
			$users[$user_id]['permissions'] = $result['permissions'];
		}
		$task['users'] = $users;
	}

	function add_user_to_task($task,$new_user = null){
		global $user,$services;
		// check
		assert(array_key_exists('id',$task),'$task array does not contain "id"');
		assert(array_key_exists('id', $new_user),'$new_user array does not contain "id"');
		assert(array_key_exists('permission', $new_user),'$new_user array does not contain "permission"');
		assert(is_numeric($new_user['permission']),'new_user[permission] must be numeric, is '.$new_user['permission']);

		$args = [':tid'=>$task['id'],':uid'=>$new_user['id']];

		$db = get_or_create_db();


		if ($new_user['permission'] == 0){ // deassign
			$query = $db->prepare('DELETE FROM tasks_users WHERE task_id = :tid AND user_id = :uid AND permissions != :perm;');
			$args[':perm'] = TASK_PERMISSION_OWNER;
			assert($query->execute($args),'Was not able to remove user from task!');
		} else { // assign
			$query = $db->prepare('SELECT user_id FROM tasks_users WHERE task_id = :tid AND user_id = :uid');
			assert($query->execute($args),'Was not able to request task assignment!');
			$rows = $query->fetchAll();

			$args[':perm'] = $new_user['permission'];
			if (empty($rows)){
				$query = $db->prepare('INSERT INTO tasks_users (task_id, user_id, permissions) VALUES (:tid, :uid, :perm );');
			} else {
				$query = $db->prepare('UPDATE tasks_users SET permissions = :perm WHERE task_id = :tid AND user_id = :uid ;');
			}
			assert($query->execute($args),'Was not able to write task assignment!');
			// share tags
			if (isset($services['bookmark'])){
				$url = getUrl('task',$task['id'].'/view');
				request('bookmark','index',['share_user_id'=>$new_user['id'],'share_url_hash'=>sha1($url)]);
			}

			// notify if newly assigned
			if (empty($rows) && param('notify') == 'on'){
				$sender = $user->email;
				$reciever = $new_user['email'];
				$subject = t('? assigned you to a task',$user->login);
				$text = t('You have been assigned to the task "?": ',$task['name']).getUrl('task',$task['id'].'/view');
				if ($sender != $reciever) send_mail($sender, $reciever, $subject, $text);
				info('Notification email has been sent.');
			}
		}
	}

	function find_project($task_id){
		$db = get_or_create_db();
		$query = $db->prepare('SELECT project_id, parent_task_id FROM tasks WHERE id = :id;');
		assert($query->execute(array(':id'=>$task_id)),'Was not able to read tasks parent or project');
		$data = reset($query->fetchAll(PDO::FETCH_ASSOC));
		if (isset($data['project_id'])) return $data['project_id'];
		if (isset($data['parent_task_id'])) return find_project($data['parent_task_id']);
		return null;
	}

	function remove_user_from_task($user_id,$task_id){
		$db = get_or_create_db();
		$query = $db->prepare('DELETE FROM tasks_users WHERE task_id = :tid AND user_id = :uid;');
		assert($query->execute([':tid'=>$task_id,':uid'=>$user_id]),'Was not able to remove user from task.');
	}

	function withdraw_user($user_id,$project_id){
		global $user;
		$db = get_or_create_db();

		$args = [':pid'=>$project_id,':old'=>$user_id,':new'=>$user->id];


		$select = 'SELECT id FROM tasks LEFT JOIN tasks_users ON tasks.id = tasks_users.task_id WHERE user_id = :old AND project_id = :pid';

		$query = $db->prepare('DELETE FROM tasks_users WHERE user_id = :new AND task_id IN ('.$select.')');
		assert($query->execute($args),'Was not able to strip your current permissions from user`s tasks!');

		$query = $db->prepare('UPDATE tasks_users SET permissions='.TASK_PERMISSION_OWNER.', user_id= :new WHERE user_id = :old and task_id IN ('.$select.')');
		assert($query->execute($args),'Was not able to assign user`s tasks to you!');
	}

	function send_note_notification($task, $note_id = null){
		global $user;
		$subject = t('? added a note.',$user->login);
		$text = t("Open the following site to see the note on \"?\":\n\n?",[$task['name'],getUrl('task',$task['id'].'/view'.(empty($note_id)?'':'#bkmk'.$note_id))]);
		$recipients = [];
		foreach ($task['users'] as $u){
			if ($u['email'] != $user->email) $recipients[] = $u['email'];
		}
		send_mail($user->email, $recipients, $subject, $text);
		info('Sent email notification to users of this task.');
	}

	function getRandomTaskId(){
		global $user;
		$db = get_or_create_db();
		$sql = 'SELECT id,status FROM tasks_users LEFT JOIN tasks ON id=task_id WHERE user_id = :uid AND status < '.TASK_STATUS_COMPLETE.' ORDER BY RANDOM() LIMIT 1';
		$query = $db->prepare($sql);
		assert($query->execute([':uid'=>$user->id]),'Was not able to read tasks table.');
		$rows = $query->fetchAll(INDEX_FETCH);
		$query->closeCursor();
		return reset(array_keys($rows));
	}

	function write_access($task){
		global $user;
		return in_array($task['users'][$user->id]['permissions'],[TASK_PERMISSION_OWNER,TASK_PERMISSION_READ_WRITE]);
	}

	function task_to_html($task){
		return '<h1>'.$task['name']."</h1>\n".$task['description']."\n";
	}

	class Task extends UmbrellaObjectWithId{
		const PERMISSION_OWNER = 1;
		const PERMISSION_READ_WRITE = 2;
		const PERMISSION_READ = 4;

		static function table(){
			return [
				'id'=> [ 'INTEGER', 'KEY'=>'PRIMARY'],
				'project_id'=>['INT','NOT NULL'],
				'parent_task_id'=>['INT','DEFAULT'=>'NULL'],
				'name'=>['VARCHAR'=>255,'NOT NULL'],
				'description'=>'TEXT',
				'status'=>['INT','DEFAULT'=>TASK_STATUS_OPEN],
				'est_time'=>['DOUBLE','DEFAULT'=>'NULL'],
				'start_date'=>'DATE',
				'due_date'=>'DATE'
			];
		}
		static function users_table(){
			return [
				'task_id'=>['INT','NOT NULL'],
				'user_id'=>['INT','NOT NULL'],
				'permissions'=>['INT','DEFAULT'=>Task::PERMISSION_OWNER],
				'PRIMARY KEY'=>['task_id','project_id']
			];
		}
		static function dependencies_table(){
			return [
					'task_id'=>['INT','NOT NULL'],
					'required_task_id'=>['INT','NOT NULL'],
					'PRIMARY KEY'=>['task_id','required_task_id']
			];
		}

		static function load($options = []){
			global $user;

			$db = get_or_create_db();
			update_task_states($db);

			$ids_only = isset($options['ids_only']) && $options['ids_only'];

			$sql = 'SELECT id';
			$where = [];
			$args = [];

			if (!$ids_only) { // if we request more than the task_ids: limit task list to user's tasks
				$sql .= ',*';
				$where[] = 'id IN (SELECT task_id FROM tasks_users WHERE user_id = ?)';
				$args[] = $user->id;
			}

			$sql .= ' FROM tasks';

			$single = false;
			if (isset($options['ids'])){
				$ids = $options['ids'];
				if (!is_array($ids) && $ids = [$ids]) $single = true;
				$qMarks = str_repeat('?,', count($ids)-1).'?';
				$where[] = 'id IN ('.$qMarks.')';
				$args = array_merge($args, $ids);
			}

			if (isset($options['project_ids'])){
				$ids = $options['project_ids'];
				if (!is_array($ids)) $ids = [$ids];
				$qMarks = str_repeat('?,', count($ids)-1).'?';
				$where[] = 'project_id IN ('.$qMarks.')';
				$args = array_merge($args, $ids);
			}

			if (isset($options['key'])){
				$key = '%'.$options['key'].'%';
				$where[] = '(name LIKE ? OR description LIKE ?)';
				$args = array_merge($args, [$key,$key]);
			}

			if (!empty($where)) $sql .= ' WHERE '.implode(' AND ', $where);

			if (!isset($options['order'])) $options['order'] = 'due_date';
			$MAX_DATE = "'9999-99-99'";
			switch ($options['order']){
				case 'due_date':
					$sql .= ' ORDER BY (CASE due_date WHEN "" THEN '.$MAX_DATE.' ELSE IFNULL(due_date,'.$MAX_DATE.') END), status COLLATE NOCASE';
					break;
				case 'name':
					$sql .= ' ORDER BY name COLLATE NOCASE';
					break;
				case 'project_id':
					$sql .= ' ORDER BY project_id, status, due_date COLLATE NOCASE';
					break;
				case 'parent_task_id':
					$sql .= ' ORDER BY project_id, parent_task_id, status, due_date COLLATE NOCASE';
					break;
				case 'start_date':
					$sql .= ' ORDER BY (CASE start_date WHEN "" THEN '.$MAX_DATE.' ELSE IFNULL(start_date,'.$MAX_DATE.') END), status COLLATE NOCASE';
					break;
				case 'status':
					$sql .= ' ORDER BY status, due_date COLLATE NOCASE';
					break;
			}

			$query = $db->prepare($sql);
			assert($query->execute($args),'Was not able to load tasks!');
			$rows = $query->fetchAll(INDEX_FETCH);

			$tasks = [];
			foreach ($rows as $row){
				$task = new Task();
				$task->patch($row);
				unset($task->dirty);
				if ($single) return $task;
				$tasks[$task->id] = $task;
			}
			if ($single) return null;
			return $tasks;
		}

		public function parent(){
			if (empty($this->parent_task_id)) return null;
			if (empty($this->parent)) $this->parent = Task::load(['ids'=>$this->parent_task_id]);
			return $this->parent;
		}

		public function load_children($levels = 0){
			$db = get_or_create_db();
			$query = $db->prepare('SELECT id,* FROM tasks WHERE parent_task_id = :id ORDER BY name ASC');
			assert($query->execute([':id'=>$this->id]),'Was not able to query children of '.$this->name);
			$rows = $query->fetchAll(INDEX_FETCH);
			$child_time_sum = 0;
			$children = [];
			foreach ($rows as $id => $row){
				$task = new Task();
				$task->patch($row);
				unset($task->dirty);
				if ($levels>0) $task->load_children($levels-1);
				$children[$task->id] = $task;
				$child_time_sum += $task->est_time;
				if (!empty($task->est_time_children)) $child_time_sum += $task->est_time_children;

			}
			$this->children = $children;
			$this->est_time_children = $child_time_sum;
			return $this;
		}

		public function load_requirements(){
			$db = get_or_create_db();
			$query = $db->prepare('SELECT id,* FROM tasks WHERE id IN (SELECT required_task_id FROM task_dependencies WHERE task_id = :id) ORDER BY status,name');
			assert($query->execute(array(':id'=>$this->id)),'Was not able to query requirements of '.$task['name']);

			$rows = $query->fetchAll(INDEX_FETCH);
			$required_tasks = [];
			foreach ($rows as $id => $row){
				$task = new Task();
				$task->patch($row);
				unset($task->dirty);
				$required_tasks[$task->id] = $task;
			}
			$this->requirements = $required_tasks;
			return $this;
		}

		public function project(){
			if (empty($this->project)) $this->project = request('project','json',['ids'=>$this->project_id,'users'=>true]);
			return $this->project;
		}

		public function load_users(){
			$db = get_or_create_db();

			$project = $this->project();
			$query = $db->prepare('SELECT * FROM tasks_users WHERE task_id = :id');
			assert($query->execute([':id'=>$this->id]));
			$rows = $query->fetchAll(PDO::FETCH_ASSOC);
			$users = [];
			foreach ($rows as $row){
				$uid = $row['user_id'];
				$project_user = isset($project['users'][$uid])?$project['users'][$uid]['data']:[];
				$project_user['permissions'] = $row['permissions'];
				$users[$uid] = $project_user;
			}

			$this->users = $users;
			return $this;
		}

		public function is_writable(){
			global $user;
			return in_array($this->users[$user->id]['permissions'],[TASK_PERMISSION_OWNER,TASK_PERMISSION_READ_WRITE]);
		}

		public function send_note_notification($note_id = null){
			global $user;
			if (empty($note_id)) return;
			$subject = t('? added a note.',$user->login);
			$text = t("Open the following site to see the note on \"?\":\n\n?",[$this->name,getUrl('task',$this->id.'/view#bkmk'.$note_id)]);
			$recipients = [];
			foreach ($task->users as $u){
				if ($u['email'] != $user->email) $recipients[] = $u['email'];
			}
			send_mail($user->email, $recipients, $subject, $text);
			info('Sent email notification to users of this task.');
		}
		public function description(){
			if (file_exists('../lib/parsedown/Parsedown.php')){
				include_once '../lib/parsedown/Parsedown.php';
				return Parsedown::instance()->parse($this->description);
			}
			return str_replace("\n", "<br/>", $this->description);
		}

		public static function perm_name($permission){
			switch ($permission){
				case Task::PERMISSION_OWNER:      return t('owner');
				case Task::PERMISSION_READ:       return t('read only');
				case Task::PERMISSION_READ_WRITE: return t('read + write');
			}
			return null;
		}
	}
?>
