<?php
	include '../bootstrap.php';

	const MODULE = 'Task';
	$title = t('Umbrella Task Management');

	function get_or_create_db(){
		$table_filename = 'tasks.db';
		if (!file_exists('db') && !mkdir('db')) throw new Exception('Failed to create task/db directory!');
		if (!is_writable('db')) throw new Exception('Directory task/db not writable!');
		if (!file_exists('db/'.$table_filename)){
			$db = new PDO('sqlite:db/'.$table_filename);

			$tables = [
					'tasks'=>Task::table(),
					'tasks_users'=>Task::users_table(),
					'task_dependencies'=>Task::dependencies_table(),
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
									if ($prop_v != 'PRIMARY') throw new Exception('Non-primary keys not implemented in task/controller.php!');
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
				if (!$query->execute()) throw new Exception('Was not able to create '.$table.' table in '.$table_filename.'!');
			}
		} else {
			$db = new PDO('sqlite:db/'.$table_filename);
		}
		return $db;
	}

	class Task extends UmbrellaObjectWithId{
		const NO_PERMISSION = 0;
		const PERMISSION_CREATOR = 1;
		const PERMISSION_READ_WRITE = 2;
		const PERMISSION_READ = 4;
		const PERMISSION_ASSIGNEE = 3;

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
				'due_date'=>'DATE',
				'show_closed'=>['BOOLEAN','DEFAULT'=>0],
				'no_index'=>['BOOLEAN','DEFAULT'=>0],
			];
		}
		static function users_table(){
			return [
				'task_id'=>['INT','NOT NULL'],
				'user_id'=>['INT','NOT NULL'],
				'permissions'=>['INT','DEFAULT'=>Task::PERMISSION_CREATOR],
				'PRIMARY KEY'=>['task_id','user_id']
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
			Task::update_states($db);

			$load_closed = !empty($options['load_closed']) && $options['load_closed']===true;
			$ids_only = isset($options['ids_only']) && $options['ids_only'];

			$sql = 'SELECT id';
			$where = [];
			$args = [];

			if (!$ids_only) { // if we request more than the task_ids: limit task list to user's tasks
				$sql .= ',*';
				$where[] = 'id IN (SELECT task_id FROM tasks_users WHERE user_id = ?)';
				$args[] = $user->id;
				$load_closed = true;
			}

			$sql .= ' FROM tasks';

			$single = false;
			if (isset($options['ids'])){
				$ids = $options['ids'];
				if (!is_array($ids) && $ids = [$ids]) $single = true;
				$qMarks = str_repeat('?,', count($ids)-1).'?';
				$where[] = 'id IN ('.$qMarks.')';
				$args = array_merge($args, $ids);
				$load_closed = true;
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
				$load_closed = true;
			}

			if (array_key_exists('parent_task_id',$options)){
				if ($options['parent_task_id'] === null){
					$where[] = 'parent_task_id IS NULL';
				} else {
					$where[] = 'parent_task_id = ?';
					$args[] = $options['parent_task_id'];
				}
			}

			if (!$load_closed){
				$where[] = 'status < ?';
				$args[] = TASK_STATUS_COMPLETE;
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
			//debug(query_insert($query, $args));
			if (!$query->execute($args)) throw new Exception('Was not able to load tasks!');
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

		/* end of static functions */

		public function assign_user($user_id){
			if (empty($this->id)) throw new Exception('invalid task id passed!');
			$db = get_or_create_db();
			$query = $db->prepare('REPLACE INTO tasks_users (task_id, user_id, permissions) VALUES (:tid, :uid, :perm)');
			if (!$query->execute([':tid'=>$this->id,':uid'=>$user_id,':perm'=>Task::PERMISSION_ASSIGNEE])) throw new Exception('Was not able to remove user from task.');
		}

		public function parent($field = null){
			if (empty($this->parent_task_id)) return null;
			if (empty($this->parent)) $this->parent = Task::load(['ids'=>$this->parent_task_id]);
			if ($field){
				if (empty($this->parent)) return null;
				return $this->parent->{$field};
			}
			return $this->parent;
		}

		public function requirements($req_id = null){
			if (empty($this->requirements)){
				$db = get_or_create_db();
				$query = $db->prepare('SELECT id,* FROM tasks WHERE id IN (SELECT required_task_id FROM task_dependencies WHERE task_id = :id) ORDER BY status,name');
				if (!$query->execute([':id'=>$this->id])) throw new Exception('Was not able to query requirements of '.$this->name);

				$rows = $query->fetchAll(INDEX_FETCH);
				$required_tasks = [];

				foreach ($rows as $row){
					$task = new Task();
					$task->patch($row);
					unset($task->dirty);
					$required_tasks[$task->id] = $task;
				}
				$this->requirements = $required_tasks;
			}
			if ($req_id){
				if (empty($this->requirements)) return null;
				if (array_key_exists($req_id,$this->requirements)) return $this->requirements[$req_id];
				return null;
			}
			return $this->requirements;
		}

		public function project($field = null){
			if (empty($this->project)) $this->project = request('project','json',['id'=>$this->project_id,'users'=>true],false,OBJECT_CONVERSION);
			if ($field){
				if (empty($this->project)) return null;
				return $this->project->{$field};
			}
			return $this->project;
		}

		public function users($id = null){
			if (empty($this->users)){
				$db = get_or_create_db();

				$project = $this->project();
				//debug($project);
				$query = $db->prepare('SELECT * FROM tasks_users WHERE task_id = :id');
				if (!$query->execute([':id'=>$this->id])) throw new Exception('Was not able to read users for task');
				$rows = $query->fetchAll(PDO::FETCH_ASSOC);
				$users = [];
				foreach ($rows as $row){
					$uid = $row['user_id'];
					$project_user = isset($project->users->{$uid})?$project->users->$uid->data:null;
					$project_user->permissions = $row['permissions'];
					$users[$uid] = $project_user;
				}
				$this->users = $users;
			}
			if (!empty($id)) return $this->users[$id];
			return $this->users;
		}

		public function is_writable(){
			global $user;
			return in_array($this->users($user->id)->permissions,[Task::PERMISSION_CREATOR,Task::PERMISSION_READ_WRITE,Task::PERMISSION_ASSIGNEE]);
		}

		public static function perm_name($permission){
			switch ($permission){
				case Task::PERMISSION_ASSIGNEE: return t('assignee');
				case Task::PERMISSION_CREATOR:      return t('owner');
				case Task::PERMISSION_READ:       return t('read only');
				case Task::PERMISSION_READ_WRITE: return t('read + write');
			}
			return null;
		}

		public function update(){
			global $services,$user;

			// check
			if (empty($this->id)) throw new Exception("task does not contain id");

			// save
			$db = get_or_create_db();
			$query = $db->prepare('UPDATE tasks SET name = :name, project_id = :pid, parent_task_id = :parent, description = :desc, est_time = :est, start_date = :start, due_date = :due, show_closed = :closed, no_index = :nidx, status = :status WHERE id = :id;');
			$args = [
					':id'=>$this->id,
					':name'=>$this->name,
					':pid'=>$this->project_id,
					':parent'=>$this->parent_task_id,
					':desc'=>$this->description,
					':est'=>$this->est_time,
					':start'=>$this->start_date,
					':due'=>$this->due_date,
					':closed'=>$this->show_closed,
					':nidx'=>$this->no_index,
					':status'=>$this->status
			];
			if (!$query->execute($args)) throw new Exception('Was not able to alter task entry in dtabase');
			unset($this->dirty);
			$hash = isset($services['bookmark']) ? $this->setTags($this->name,$this->id) : false;

			if (param('silent','off') != 'on'){ // [notify] task users
				$users = $this->users();
				$subject = t('◊ edited one of your tasks',$user->login);
				$text = t("The task \"◊\" now has the following description:\n\n◊\n\n",[$this->name,$this->description]).getUrl('task',$this->id.'/view');
				$meta = [
						'project_id'=>$this->project_id,
						'task_id'=>$this->id,
				];
				request('user','notify',['subject'=>$subject,'body'=>$text,'recipients'=>array_keys($users),'meta'=>$meta]);

				if ($hash) {
					foreach ($users as $uid => $u) request('bookmark','index',['share_user_id'=>$uid,'share_url_hash'=>$hash,'notify'=>false]);
				}
			}
			return $this;
		}

		public function save(){
			global $user,$services;
			if (empty($this->name)) throw new Exception('Task name must be set!');
			if (!is_numeric($this->project_id)) throw new Exception('Task must reference existing project!');
			if (!empty($this->est_time) && !is_numeric($this->est_time)) throw_exception('"◊" is not a valid duration!',$this->est_time);

			$start_stamp = null;
			if (!empty($this->start_date)){
				$start_stamp = strtotime($this->start_date);
				if ($start_stamp === false) throw_exception('Start date (◊) is not a valid date!',$this->start_date);
				if ($start_stamp > time()) $this->status = TASK_STATUS_PENDING;
			} else $this->start_date = null;

			if (!empty($this->due_date)){
				$due_stamp = strtotime($this->due_date);
				if ($due_stamp === false) throw_exception('Due date (◊) is not a valid date!', $this->due_date);
				if ($start_stamp && $start_stamp > $due_stamp){
					$this->start_date = $this->due_date;
					info('Start date adjusted to match due date!');
				}
			} else $this->due_date = null;

			if (isset($this->id)) return $this->update();
			$db = get_or_create_db();

			$this->status = TASK_STATUS_OPEN;

			$query = $db->prepare('INSERT INTO tasks (name, project_id, parent_task_id, description, status, est_time, start_date, due_date, show_closed, no_index) VALUES (:name, :pid, :parent, :desc, :state, :est, :start, :due, :closed, :nidx);');
			$args = [
					':name'=>$this->name,
					':pid'=>$this->project_id,
					':parent'=>$this->parent_task_id,
					':desc'=>$this->description,
					':state'=>$this->status,
					':est'=>$this->est_time,
					':start'=>$this->start_date,
					':due'=>$this->due_date,
					':closed'=>$this->show_closed,
					':nidx'=>$this->no_index,
			];
			//if ($this->name=="task five")debug(['task'=>$this,'args'=>$args,'query'=>query_insert($query, $args)],1);
			if (!$query->execute($args)) throw new Exception('Was not able to create new task entry in database');
			$this->id = $db->lastInsertId();

			$user->permission = Task::PERMISSION_CREATOR;
			$this->add_user($user);

			unset($this->dirty);


			$hash = isset($services['bookmark']) ? $this->setTags($this->name,$this->id) : false;

			$notify = (param('notify')=='on');
			foreach ($this->users as $id => $new_user) {
				if ($id == $user->id) continue;
				$this->add_user($new_user,$notify);
				if ($hash) request('bookmark','index',['share_user_id'=>$id,'share_url_hash'=>$hash,'notify'=>false]);
			}
			return $this;
		}

		public function update_requirements($required_task_ids){
			$db = get_or_create_db();

			if (empty($required_task_ids)) {
				$query = $db->prepare('DELETE FROM task_dependencies WHERE task_id = :tid');
				$query->execute([':tid'=>$this->id]);
				return $this;
			}
			if (!is_array($required_task_ids)) throw_exception('Required tasks should be a list, ◊ found!',$required_task_ids);
			$required_task_ids = array_keys($required_task_ids);

			$qmarks = implode(',', array_fill(0, count($required_task_ids), '?'));
			$args = $required_task_ids;
			$args[] = $this->id;

			$query = $db->prepare('DELETE FROM task_dependencies WHERE required_task_id NOT IN ('.$qmarks.') AND task_id = ?');
			$query->execute($args);
			$query = $db->prepare('INSERT INTO task_dependencies (task_id, required_task_id) VALUES (:id, :req);');
			foreach ($required_task_ids as $rid) {
				if (empty($rid)) continue;
				$query->execute([':id'=>$this->id,':req'=>$rid]);
			}
			return $this;
		}

		public function add_user($new_user = null, $notify = false){
			global $user,$services;
			// check
			if (empty($this->id)) throw new Exception('Task does not contain "id"');
			if (empty($new_user->id)) throw new Exception('$new_user does not contain id');
			if (!isset($new_user->permission)) throw new Exception('$new_user does not contain permission');
			$permission = $new_user->permission;
			switch ($permission){
				case Task::PERMISSION_CREATOR:
				case Task::NO_PERMISSION: // also catches false and null
				case Task::PERMISSION_READ:
				case Task::PERMISSION_READ_WRITE:
				case Task::PERMISSION_ASSIGNEE:
					break;
				default:
					error('Invalid permission set for ◊',$new_user->login);
					return false;
			}

			$args = [':tid'=>$this->id,':uid'=>$new_user->id];

			$db = get_or_create_db();

			if ($new_user->permission == Task::NO_PERMISSION){ // deassign
				$query = $db->prepare('DELETE FROM tasks_users WHERE task_id = :tid AND user_id = :uid AND permissions != '.Task::PERMISSION_CREATOR.';'); // do not delete creator!
				if (!$query->execute($args)) throw new Exception('Was not able to remove user from task!');
			} else { // assign
				$query = $db->prepare('SELECT user_id FROM tasks_users WHERE task_id = :tid AND user_id = :uid');
				if (!$query->execute($args)) throw new Exception('Was not able to request task assignment!');
				$rows = $query->fetchAll();
				$args[':perm'] = $new_user->permission;
				if (empty($rows)){
					$query = $db->prepare('INSERT INTO tasks_users (task_id, user_id, permissions) VALUES (:tid, :uid, :perm );');
				} else {
					$query = $db->prepare('UPDATE tasks_users SET permissions = :perm WHERE task_id = :tid AND user_id = :uid AND permissions != '.Task::PERMISSION_CREATOR.';'); // do not alter creator!
				}

				if (!$query->execute($args)) throw new Exception('Was not able to write task assignment!');
				// share tags
				if (isset($services['bookmark'])){
					$url = getUrl('task',$this->id.'/view');
					request('bookmark','index',['share_user_id'=>$new_user->id,'share_url_hash'=>sha1($url)]);
				}

				// notify if newly assigned
				if ($notify && empty($rows) && ($user->email != $new_user->email)) {
					$subject = t('◊ assigned you to a task',$user->login);
					$text = t('You have been assigned to the task "◊": ',$this->name).getUrl('task',$this->id.'/view');
					$meta = [
							'project_id'=>$this->project_id,
							'task_id'=>$this->id,
					];
					request('user','notify',['subject'=>$subject,'body'=>$text,'recipients'=>$new_user->id,'meta'=>$meta]);
					info('Notification email has been sent.');
				}
			}
			return true;
		}

		public function set_state($state){
			$db = get_or_create_db();
			if (empty($this->id)) throw new Exception('invalid task id passed!');
			if (!is_numeric($state)) throw new Exception('invalid state passed!');
			$query = $db->prepare('UPDATE tasks SET status = :state WHERE id = :id;');
			if (!$query->execute(array(':state' => $state,':id'=>$this->id))) throw new Exception('Was not able to alter task state in database');
		}

		public function delete(){
			if (empty($this->id)) throw new Exception('invalid task id passed!');
			$db = get_or_create_db();
			$args = [':id'=>$this->id];

			$query = $db->prepare('DELETE FROM tasks WHERE id = :id');
			if (!$query->execute([':id'=>$this->id]))throw new Exception('Was not able to delete task!');

			$query = $db->prepare('DELETE FROM task_dependencies WHERE task_id = :id');
			if (!$query->execute([':id'=>$this->id])) throw new Exception('Was not able to delete task!');

			$query = $db->prepare('DELETE FROM tasks_users WHERE task_id = :id');
			if (!$query->execute([':id'=>$this->id])) throw new Exception('Was not able to delete task_users entry!');

			$args[':ptid']= $this->parent_task_id;
			$query = $db->prepare('UPDATE tasks SET parent_task_id = :ptid WHERE parent_task_id = :id');
			if (!$query->execute($args)) throw new Exception('Was not able to update parent of dependent tasks');
			info('Task has been deleted.');
		}

		public function drop_user($user_id){
			if (empty($this->id)) throw new Exception('invalid task id passed!');
			$db = get_or_create_db();
			$query = $db->prepare('DELETE FROM tasks_users WHERE task_id = :tid AND user_id = :uid;');
			if (!$query->execute([':tid'=>$this->id,':uid'=>$user_id])) throw new Exception('Was not able to remove user from task.');
		}

		public function children($recurse = true, $load_closed = false, $db = null){
			if (empty($this->children))	{
				if ($db == null ) $db = get_or_create_db();
				$query = $db->prepare('SELECT id,* FROM tasks WHERE parent_task_id = :id ORDER BY name COLLATE NOCASE ASC');
				if (!$query->execute([':id'=>$this->id])) throw new Exception('Was not able to query children of '.$this->name);
				$rows = $query->fetchAll(INDEX_FETCH);
				$children = [];
				foreach ($rows as $row){
					$task = new Task();
					$task->patch($row);
					if (!$load_closed && $task->status >= TASK_STATUS_COMPLETE) continue;
					unset($task->dirty);
					if ($recurse) $task->children($recurse,$load_closed,$db); // load children recursively
					$children[$task->id] = $task;
				}
				$this->children = $children;
			}
			return $this->children;
		}

		public function child_time(){
			$sum = 0;
			foreach ($this->children() as $child) {
				$sum += empty($child->est_time) ? 0 : $child->est_time;
				$ct = $child->child_time();
				$sum += empty($ct) ? 0 : $ct;				
			}
			return $sum;
		}

		public function update_project($new_pid){
			foreach ($this->children() as $tid => $child) $child->update_project($new_pid);
			$this->patch(['project_id'=>$new_pid])->save();
		}

		public static function random_id(){
			global $user;

			$projects = request('project','json');
			foreach ($projects as $id => $project) {
				if (in_array($project['status'], [PROJECT_STATUS_CANCELED,PROJECT_STATUS_COMPLETE])) unset($projects[$id]);
			}
			$args = array_keys($projects);
			$where = ['project_id IN ('.implode(',', array_fill(0, count($args), '?')).')'];

			$where[] = 'user_id = ?';
			$args[] = $user->id;

			$where[] = 'status not in (?,?)';
			$args[] = TASK_STATUS_CANCELED;
			$args[] = TASK_STATUS_COMPLETE;

			$where[] = 'no_index = 0';

			$db = get_or_create_db();
			$sql = 'SELECT id,status FROM tasks_users LEFT JOIN tasks ON id=task_id WHERE '.implode(' AND ',$where).' ORDER BY RANDOM() LIMIT 1';
			$query = $db->prepare($sql);
			if (!$query->execute($args)) throw new Exception('Was not able to read tasks table.');
			$rows = $query->fetchAll(INDEX_FETCH);
			$query->closeCursor();
			return reset(array_keys($rows));
		}

		public static function withdraw_user_from_project($user_id,$project_id){
			global $user;
			$db = get_or_create_db();

			$args = [':pid'=>$project_id,':old'=>$user_id,':new'=>$user->id];

			$select = 'SELECT id FROM tasks LEFT JOIN tasks_users ON tasks.id = tasks_users.task_id WHERE user_id = :old AND project_id = :pid';

			$query = $db->prepare('DELETE FROM tasks_users WHERE user_id = :new AND task_id IN ('.$select.')');
			if (!$query->execute($args)) throw new Exception('Was not able to strip your current permissions from user`s tasks!');

			$query = $db->prepare('UPDATE tasks_users SET permissions='.Task::PERMISSION_CREATOR.', user_id= :new WHERE user_id = :old and task_id IN ('.$select.')');
			if (!$query->execute($args)) throw new Exception('Was not able to assign user`s tasks to you!');
		}

		public static function update_states($db){
			$date = '"'.date('Y-m-d').'"';
			$db->exec('UPDATE tasks SET status = '.TASK_STATUS_OPEN.' WHERE status = '.TASK_STATUS_PENDING.' AND start_date != "" AND start_date <= '.$date);
			$db->exec('UPDATE tasks SET status = '.TASK_STATUS_OPEN.' WHERE status = '.TASK_STATUS_PENDING.' AND due_date != "" AND due_date <= '.$date);
			$db->exec('UPDATE tasks SET status = '.TASK_STATUS_PENDING.' WHERE status = '.TASK_STATUS_OPEN.' AND start_date != "" AND start_date > '.$date);
		}

		private function setTags(){
			if ($raw_tags = param('tags')){
				$raw_tags = explode(' ', str_replace(',',' ',$raw_tags));
				$tags = [];
				foreach ($raw_tags as $tag){
					if (trim($tag) != '') $tags[]=$tag;
				}
				$url = getUrl('task',$this->id.'/view');
				request('bookmark','add',['url'=>$url,'comment'=>t('Task: ◊',$this->name),'tags'=>$tags]);
				return sha1($url);
			}
			return false;
		}

	}
?>
