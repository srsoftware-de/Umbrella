<?php $title = 'Umbrella Project Importer';


const STATE_READY = 1;
const STATE_MISSING_MYSQL_PARAMETER = 2;
const STATE_NO_DB_CONNECTION = 3;
const STATE_NO_SOURCE_PROJECTS = 4;
const STATE_SOURCE_PROJECT_UNSET = 5;
const STATE_MISSING_TARGET_PROJECT = 6;
const STATE_NO_TARGET_PROJECTS = 7;
const STATE_NO_TASK_DB_ACCESS = 8;
const STATE_TASK_CREATION_FAILED = 9;
const STATE_NO_TARGET_USERS = 10;
const STATE_NOT_LOGGED_IN = 11;
const STATE_TASK_ASSIGNMENT_FAILED = 11;

const SRC_STATUS_CLOSED = 0;
const SRC_STATUS_OPEN = 1;

const DST_STATUS_OPEN = 10;
const DST_STATUS_CLOSED = 60;

include '../bootstrap.php';



$state = STATE_READY;

/* Collect MYSQL parameters */
$mysql  = param('mysql');
if ($mysql) {
	foreach (['host','port','user','name','pass'] as $field) {
		if (!isset($mysql[$field]) || $mysql[$field] == '') $state = STATE_MISSING_MYSQL_PARAMETER;
	}
} else $state = STATE_MISSING_MYSQL_PARAMETER;

/* Try to establish connection to source DATABASE */
if ($state == STATE_READY){
	$dsn = 'mysql:host='.$mysql['host'].';port='.$mysql['port'].';dbname='.$mysql['name'].';charset=UTF8';
	try {
		$source_db = new PDO($dsn, $mysql['user'], $mysql['pass']);
	} catch (Exception $e){
		error($e->getMessage());
		$state = STATE_NO_DB_CONNECTION;
	}
}

if ($state == STATE_READY){
	$query = $source_db->prepare('SELECT ID,Name FROM projekte ORDER BY Name');
	$query->execute();
	$src_projects = $query->fetchAll(INDEX_FETCH);
	if (!is_array($src_projects) || empty($src_projects)) {
		error('No source projects found!');
		$state = STATE_NO_SOURCE_PROJECTS;
	} 
}

if ($state == STATE_READY){
	$source_project_id = param('source')['project_id'];
	if (!$source_project_id) $state = STATE_SOURCE_PROJECT_UNSET;
}


if ($state == STATE_READY){
	$projects = request('project','json');
	if (!$projects || empty($projects)){
		error('No target projects found! Make sure you are logged in to umbrella and there is at least one project!');
		$state = STATE_NO_TARGET_PROJECTS;
	}
}

if ($state == STATE_READY){	
	$target = param('target');
	if ($target){
		foreach (['project'] as $field){
			if (!isset($target[$field])) $state = STATE_MISSING_TARGET_PROJECT;
		}
	} else $state = STATE_MISSING_TARGET_PROJECT;
}

if ($state == STATE_READY){
	$all_users = request('user','list');
	if (!$all_users || empty($all_users)){
		error('Was not able to load user list Make sure you are logged in to umbrella.');
		$state = STATE_NOT_LOGGED_IN;		
	}
	$user_ids = request('project','user_list',['id'=>$target['project']]);
	if (!$user_ids || empty($user_ids)){
		error('No users found in target project. Imported tasks need to be assigned to at least one user. Thus, you need to add at least one user to the target project.');
		$state = STATE_NO_TARGET_USERS;
	}
	$users = [];
	foreach ($user_ids as $uid => $dummy){
		$users[$uid] = $all_users[$uid];
	}
}



if ($state == STATE_READY){
	foreach (['users'] as $field){
		if (!isset($target[$field])) $state = STATE_NO_TARGET_USERS;
	}
}


if ($state == STATE_READY){
	$task_db = new PDO('sqlite:../task/db/tasks.db');
	try {
		$query = $task_db->prepare('SELECT * FROM tasks');
		if (!$query){
			error('Was not able to access tasks.db');
			$state = STATE_NO_TASK_DB_ACCESS;
		}
	} catch (Exception $e){
		error($e->getMessage());
		$state = STATE_NO_TASK_DB_ACCESS;
	}
}

session_write_close();

if ($state == STATE_READY){
	$query = $source_db->prepare('SELECT * FROM milestones WHERE project = :pid ORDER BY Name');
	$query->execute([':pid'=>$source_project_id]);
	$milestones = $query->fetchAll(INDEX_FETCH);
	
	$find_query = $task_db->prepare('SELECT * FROM tasks WHERE project_id = :pid AND name = :name');
	
	$create_sql = 'INSERT INTO tasks (project_id, name, description, status, start_date, due_date) VALUES (:pid, :name, :desc, :status, :start, :due)';
	$create_query = $task_db->prepare($create_sql);
	
	$assign_sql = 'INSERT INTO tasks_users (task_id, user_id) VALUES (:tid, :uid)';
	$assign_query = $task_db->prepare($assign_sql);
	
	foreach ($milestones as $id => &$milestone){
		
		$find_query->execute([':pid'=>$target['project'],':name'=>$milestone['name']]);
		$existing_tasks = $find_query->fetchAll(INDEX_FETCH); 		
		
		if (empty($existing_tasks)){
			$start = date('Y-m-d',$milestone['start']);
			$due = date('Y-m-d',$milestone['end']);
			$status = $milestone['status'] == SRC_STATUS_OPEN ? DST_STATUS_OPEN : DST_STATUS_CLOSED;
			$create_args = [':pid'=>$target['project'], ':name'=>$milestone['name'], ':desc'=>$milestone['desc'], ':status'=>$status, ':start'=>$start,':due'=>$due];
			if ($create_query->execute($create_args)){
				$dst_task_id = $task_db->lastInsertId();
				$milestone['dst_task'] = $dst_task_id;
				foreach ($target['users'] as $uid){
					$assign_args = [':tid'=>$dst_task_id,':uid'=>$uid];
					if (!$assign_query->execute($assign_args)){
						$state = STATE_TASK_ASSIGNMENT_FAILED;
						error('Failed to assign task to user:');
						error(query_insert($assign_sql, $assign_args));
						break 2;						
					}
				} 				
			} else {
				$state = STATE_TASK_CREATION_FAILED;
				error('Failed to create task. Query follows:');
				error(query_insert($create_sql, $create_args));
				break;
			}
		} else {
			foreach ($existing_tasks as $dst_task_id => $task) $milestone['dst_task'] = $dst_task_id;
			warn('Task "'.$milestone['name'].'" already exists.');
		}
	}
}

if ($state == STATE_READY){
	info('Milestones imported');
	$query = $source_db->prepare('SELECT * FROM tasklist WHERE project = :pid');
	$query->execute([':pid'=>$source_project_id]);
	$tasklists = $query->fetchAll(INDEX_FETCH); 
	//debug(['MILESTONES'=>$milestones,'TASKLISTS'=>$tasklists]);
	
	$find_with_parent_query = $task_db->prepare('SELECT * FROM tasks WHERE project_id = :pid AND parent_task_id = :parent AND name = :name');
	
	$create_with_parent_sql = 'INSERT INTO tasks (parent_task_id, project_id, name, description, status, start_date, due_date) VALUES (:parent, :pid, :name, :desc, :status, :start, :due)';
	$create_with_parent_query = $task_db->prepare($create_with_parent_sql);
	
	foreach ($tasklists as $id => &$tasklist){
		if ($tasklist['milestone']){ // Tasklist is assigned to milestone
			$milestone_id = $tasklist['milestone'];
			$milestone = $milestones[$milestone_id];
			$dst_task_id = $milestone['dst_task'];
			
			if ($tasklist['name'] == $milestone['name']){ // append tasks of tasklist directly to milestone-task
				$tasklist['dst_task'] = $dst_task_id;
			} else { // create task for tasklist as child of milestone-task
				$find_with_parent_query->execute([':pid'=>$target['project'],':parent'=>$dst_task_id,':name'=>$tasklist['name']]);
				$existing_tasks = $find_with_parent_query->fetchAll(INDEX_FETCH);
				if (empty($existing_tasks)){ // tasklist-task not existing 
					$start = date('Y-m-d',$tasklist['start']);
					$due = null;
					$status = $tasklist['status'] == SRC_STATUS_OPEN ? DST_STATUS_OPEN : DST_STATUS_CLOSED;
					$create_args = [':parent'=>$dst_task_id,':pid'=>$target['project'], ':name'=>$tasklist['name'], ':desc'=>$tasklist['desc'], ':status'=>$status, ':start'=>$start,':due'=>$due];
					if ($create_with_parent_query->execute($create_args)){
						$dst_task_id = $task_db->lastInsertId();
						$tasklist['dst_task'] = $dst_task_id;
						foreach ($target['users'] as $uid){
							$assign_args = [':tid'=>$dst_task_id,':uid'=>$uid];
							if (!$assign_query->execute($assign_args)){
								$state = STATE_TASK_ASSIGNMENT_FAILED;
								error('Failed to assign task to user:');
								error(query_insert($assign_sql, $assign_args));
								break 2;
							}
						}
					} else {
						$state = STATE_TASK_CREATION_FAILED;
						error('Failed to create task. Query follows:');
						error(query_insert($create_sql, $create_args));
						break;
					}
						
				} else { // tasklist-task already exists
					foreach ($existing_tasks as $dst_task_id => $task) $tasklist['dst_task'] = $dst_task_id;
					warn('Task "'.$tasklist['name'].'" already exists.');					 
				}
			}
		} else { // Tasklist is not assigned to milestone
			$find_query->execute([':pid'=>$target['project'],':name'=>$tasklist['name']]);
			$existing_tasks = $find_query->fetchAll(INDEX_FETCH);
			
			if (empty($existing_tasks)){
				$start = date('Y-m-d',$tasklist['start']);
				$due = null;
				$status = $tasklist['status'] == SRC_STATUS_OPEN ? DST_STATUS_OPEN : DST_STATUS_CLOSED;
				$create_args = [':pid'=>$target['project'], ':name'=>$tasklist['name'], ':desc'=>$tasklist['desc'], ':status'=>$status, ':start'=>$start,':due'=>$due];
				if ($create_query->execute($create_args)){
					$dst_task_id = $task_db->lastInsertId();
					$tasklist['dst_task'] = $dst_task_id;
					foreach ($target['users'] as $uid){
						$assign_args = [':tid'=>$dst_task_id,':uid'=>$uid];
						if (!$assign_query->execute($assign_args)){
							$state = STATE_TASK_ASSIGNMENT_FAILED;
							error('Failed to assign task to user:');
							error(query_insert($assign_sql, $assign_args));
							break 2;
						}
					}
				} else {
					$state = STATE_TASK_CREATION_FAILED;
					error('Failed to create task. Query follows:');
					error(query_insert($create_sql, $create_args));
					break;
				}
			} else {
				foreach ($existing_tasks as $dst_task_id => $task) $tasklist['dst_task'] = $dst_task_id;
				warn('Task "'.$tasklist['name'].'" already exists.');
			}
		}
	}
}

if ($state == STATE_READY){
	info('Tasklists imported.');
}

include '../common_templates/head.php';
include '../common_templates/messages.php';

if ($state != STATE_READY){ ?><form method="POST"><?php }

if (in_array($state,[	STATE_MISSING_MYSQL_PARAMETER,
						STATE_NO_DB_CONNECTION,
						STATE_NO_SOURCE_PROJECTS,
						STATE_SOURCE_PROJECT_UNSET,
						STATE_SOURCE_PROJECT_UNSET,
						STATE_MISSING_TARGET_PROJECT,
						STATE_NO_TARGET_USERS,
					])) { ?>
<fieldset>
	<legend>Source</legend>
	
	<fieldset>
		<legend>MySQL host</legend>
		<input type="text" name="mysql[host]" value="<?= param('mysql')['host']?>">
	</fieldset>
	
	<fieldset>
		<legend>MySQL port</legend>
		<input type="text" name="mysql[port]" value="<?= param('mysql')['port']?param('mysql')['port']:3306 ?>">
	</fieldset>
	
	<fieldset>
		<legend>MySQL user name</legend>
		<input type="text" name="mysql[user]" value="<?= param('mysql')['user']?>">
	</fieldset>
	
	<fieldset>
		<legend>MySQL database name</legend>
		<input type="text" name="mysql[name]" value="<?= param('mysql')['name']?>">
	</fieldset>
	
	<fieldset>
		<legend>MySQL database password</legend>
		<input type="password" name="mysql[pass]" value="<?= param('mysql')['pass']?>">
	</fieldset>		
</fieldset>

<?php } // missing input

if (in_array($state, [	STATE_SOURCE_PROJECT_UNSET,
						STATE_MISSING_TARGET_PROJECT,
						STATE_NO_TARGET_USERS ])) { ?>
<fieldset>
	<legend>Source Project</legend>
	Select a project to import from:
	<select name="source[project_id]">
		<?php foreach ($src_projects as $id => $project) {?>
		<option value="<?= $id ?>" <?= $id == $source_project_id?'selected="true"':''?>><?= $project['Name']?></option>
		<?php }?>
	</select>
</fieldset>

<?php }


if (in_array($state, [ STATE_MISSING_TARGET_PROJECT,
					   STATE_NO_TARGET_USERS ])) { ?>

<fieldset>
	<legend>Target Project</legend>
	Select a project to import to:
	<select name="target[project]">
		<?php foreach ($projects as $id => $project) {?>
		<option value="<?= $id ?>" <?= $id == $target['project']?'selected="true"':''?>><?= $project['name']?></option>
		<?php }?>
	</select>
</fieldset>

<?php }

if (in_array($state, [ STATE_NO_TARGET_USERS ])) { ?>

<fieldset>
	<legend>Users</legend>
	Select users, o which imported tasks shall be assigned to:
	<?php foreach ($users as $id => $user) {?>
	<br/>
	<label><input type="checkbox" name="target[users][]" value="<?= $id ?>"><?= $user['login']?></label>
	<?php }?>	
</fieldset>

<?php }

if ($state != STATE_READY){ ?><button type="submit">Go on</button></form><?php }
debug($_POST);
debug($state);
include '../common_templates/closure.php';