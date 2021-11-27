<?php
    include '../bootstrap.php';
    
    const MODULE = 'Project';
    const DB_VERSION = 1;
    $title = t('Umbrella Project Management');
    $base_url = getUrl('project');
    
    const PROJECT_PERMISSION_OWNER = 1;
    const PROJECT_PERMISSION_PARTICIPANT = 2;
    
    $PROJECT_PERMISSIONS = array(PROJECT_PERMISSION_OWNER=>'owner',PROJECT_PERMISSION_PARTICIPANT=>'participant');
    
    function get_or_create_db(){
        $table_filename = 'projects.db';
        if (!file_exists('.db') && !mkdir('.db')) throw new Exception('Failed to create project/.db directory!');
        if (!is_writable('.db')) throw new Exception('Directory project/.db not writable!');
        if (!file_exists('.db/'.$table_filename)){
            $db = new PDO('sqlite:.db/'.$table_filename);
            
            $tables = [
                'projects'=>Project::table(),
                'projects_users'=>Project::users_table(),
                'settings'=>Settings::table()
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
                                    if ($prop_v != 'PRIMARY') throw new Exception('Non-primary keys not implemented in project/controller.php!');
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
            update($db);
        } else {
            $db = new PDO('sqlite:.db/'.$table_filename);
        }
        return $db;
    }
    
    class Project extends UmbrellaObjectWithId{
        function __construct(){
            $this->patch(['status'=>PROJECT_STATUS_OPEN]);
        }
        
        static function connected_users($options = []){
            global $user;
            $sql = 'SELECT user_id,* FROM projects_users WHERE project_id IN (SELECT project_id FROM projects_users WHERE user_id = ?)';
            $args = [$user->id];
            
            if (isset($options['ids'])){
                $ids = $options['ids'];
                if (!is_array($ids)) $ids = [$ids];
                $qmarks = str_repeat('?,', count($ids)-1).'?';
                $sql .= ' AND project_id IN ('.$qmarks.')';
                $args = array_merge($args,$ids);
            }
            
            $sql .= ' GROUP BY user_id';
            $db = get_or_create_db();
            $query = $db->prepare($sql);
            if (!$query->execute($args)) throw new Exception('Was not able to read connected users.');
            return $query->fetchAll(INDEX_FETCH);
        }
        
        static function load($options = []){
            global $user;
            $sql = 'SELECT id,* FROM projects';
            
            if ($user->id == 1 && param('grant','own')=='all'){ // grant = all is used by user/notify
                $where = [];
                $args = [];
            } else {
                $where = ['id IN (SELECT project_id FROM projects_users WHERE user_id = ?)'];
                $args = [$user->id];
            }
            
            $single = false;
            if (isset($options['ids'])){
                $ids = $options['ids'];
                if (!is_array($ids)) {
                    $ids = [$ids];
                    $single = true;
                }
                $qMarks = str_repeat('?,', count($ids)-1).'?';
                $where[] ='id IN ('.$qMarks.')';
                $args = array_merge($args, $ids);
            }
            
            if (isset($options['company_ids'])){
                $ids = $options['company_ids'];
                if (!is_array($ids)) $ids = [$ids];
                $qMarks = str_repeat('?,', count($ids)-1).'?';
                $where[] = 'company_id IN ('.$qMarks.')';
                $args = array_merge($args, $ids);
            }
            
            if (isset($options['key'])){
                $key = '%'.$options['key'].'%';
                $where[] = '(name LIKE ? OR description LIKE ?)';
                $args = array_merge($args, [$key,$key]);
            }
            
            if (!empty($where)) $sql .= ' WHERE '.implode(' AND ',$where);
            
            if (isset($options['order'])){
                switch ($options['order']){
                    case 'status':
                        $sql .= ' ORDER BY '.$options['order'].' COLLATE NOCASE';
                        break;
                    case 'company':
                        $sql .= ' ORDER BY company_id DESC';
                        break;
                }
            } else $sql .= ' ORDER BY name COLLATE NOCASE';
            
            $db = get_or_create_db();
            $query = $db->prepare($sql);
            if (!$query->execute($args)) throw new Exception('Was not able to load projects!');
            $projects = [];
            $rows = $query->fetchAll(INDEX_FETCH);
            foreach ($rows as $pid => $row){
                $project = new Project();
                $project->patch($row);
                unset($project->dirty);
                $projects[$pid] = $project;
            }
            $qMarks = str_repeat('?,', count($projects)-1).'?';
            if (isset($options['users']) && $options['users']==true){
                $sql = 'SELECT * FROM projects_users WHERE project_id IN ('.$qMarks.')';
                $query = $db->prepare($sql);
                if (!$query->execute(array_keys($projects))) throw new Exception('Was not able to load project users!');
                $rows = $query->fetchAll(PDO::FETCH_ASSOC);
                
                $uids = [];
                foreach ($rows as $row){
                    $pid = $row['project_id'];
                    $uid = $row['user_id'];
                    $projects[$pid]->users[$uid] = $row['permissions'];
                    $uids[$uid] = true;
                }
                
                $users = request('user','json',['ids'=>array_keys($uids)]);
                foreach ($projects as &$project){
                    foreach ($project->users as $id => $permission) $project->users[$id] = ['permission'=>$permission,'data'=>$users[$id]];
                }
            }
            
            if ($single) {
                if (empty($projects)) return null;
                return reset($projects);
            }
            return $projects;
        }
        
        static function table(){
            return [
                'id'=> [ 'INTEGER', 'KEY'=>'PRIMARY' ],
                'company_id' => 'INT',
                'name' => [ 'VARCHAR'=>255, 'NOT NULL' ],
                'description' => 'TEXT',
                'status' => [ 'INT', 'DEFAULT'=>PROJECT_STATUS_OPEN ],
                'show_closed' => [ 'BOOLEAN', 'DEFAULT'=>0 ]
            ];
        }
        
        static function users_table(){
            return [
                'project_id' => [ 'INT', 'NOT NULL' ],
                'user_id' => [ 'INT', 'NOT NULL' ],
                'permissions' => [ 'INT', 'DEFAULT'=>PROJECT_PERMISSION_OWNER ],
                'PRIMARY KEY' => [ 'project_id', 'user_id' ],
            ];
        }
        
        function addUser($new_user,$permission = PROJECT_PERMISSION_PARTICIPANT){
            global $user;
            if(!is_numeric($this->id)) throw new Exception('project id must be numeric, is '.$project->id);
            if(!is_array($new_user)) throw new Exception('$new_user must be user object, is '.$new_user);
            if(!is_numeric($permission)) throw new Exception('permission must be numeric, is '.$permission);
            $db = get_or_create_db();
            $query = $db->prepare('INSERT INTO projects_users (project_id, user_id, permissions) VALUES (:pid, :uid, :perm);');
            if (!$query->execute(array(':pid'=>$this->id,':uid'=>$new_user['id'], ':perm'=>$permission))) throw new Exception('Was not able to assign project to user!');
            if (param('notify') == 'on'){
                $reciever = $new_user['id'];
                $subject = t('◊ added you to a project',$user->login).' [p:'.$this->id.']';
                $text = t('You have been added to the project "◊": ◊',[$this->name,getUrl('project',$this->id.'/view')])."\n";
                $text .= t('This means you are now able to file bugs, add tasks to this projects and view the progress of this project.')."\n";
                $text .= t('Navigate to the aforementioned linkt to login to the Umbrella Project Management Suite.');
                $meta = ['project_id'=>$this->id];
                request('user','notify',['subject'=>$subject,'body'=>$text,'recipients'=>[$reciever],'meta'=>$meta]);
                info('Notification email has been sent to ◊',$new_user['login']);
            }
        }
        
        function remove_user($user_id){
            global $user;
            $db = get_or_create_db();
            
            request('task','withdraw_user',['project_id'=>$this->id,'user_id'=>$user_id]);
            
            $query = $db->prepare('DELETE FROM projects_users WHERE project_id = :pid AND user_id = :uid');
            if (!$query->execute([':pid'=>$this->id,':uid'=>$user_id])) throw new Exception('Was not able to remove user from project!');
            
            info('User has been removed from project.');
            unset($this->users[$user_id]);
        }
        
        public function save($silent = false){
            global $services,$user;
            $db = get_or_create_db();
            $known_fields = array_keys(Project::table());
            if (isset($this->id)){
                
                $sql = 'UPDATE projects SET';
                $args = [];
                
                foreach ($this->dirty as $field){
                    if (in_array($field, $known_fields)){
                        $sql .= ' '.$field.'=:'.$field.',';
                        $args[':'.$field] = $this->{$field};
                    }
                }
                
                if (!empty($args)){
                    $sql = rtrim($sql,',').' WHERE id = :id';
                    $args[':id'] = $this->id;
                    $query = $db->prepare($sql);
                    if (!$query->execute($args)) throw new Exception('Was no able to update project in database!');
                }
                
                if (!$silent){
                    $subject = t('◊ updated project "◊"',[$user->login,$this->name]).' [p:'.$this->id.']';
                    $body = t("The new description of ◊ is now:\n◊",[$this->name,$this->description]);
                    $meta = ['project_id'=>$this->id];
                    request('user','notify',['subject'=>$subject,'body'=>$body,'recipients'=>array_keys($this->users),'meta'=>$meta],false,NO_CONVERSION);
                    info('Users have been notified');
                }
            } else {
                $fields = [];
                $args = [];
                foreach ($known_fields as $f){
                    if (isset($this->{$f})){
                        $fields[]=$f;
                        $args[':'.$f] = $this->{$f};
                    }
                }
                $query = $db->prepare('INSERT INTO projects ( '.implode(', ',$fields).' ) VALUES ( :'.implode(', :',$fields).' )');
                //debug(query_insert($query, $args),1);
                if (!$query->execute($args)) throw new Exception('Was not able to insert new project');
                
                $this->id = $db->lastInsertId();
            }
            
            if (isset($services['bookmark']) && ($raw_tags = param('tags'))){
                $raw_tags = explode(' ', str_replace(',',' ',$raw_tags));
                $tags = [];
                foreach ($raw_tags as $tag){
                    if (trim($tag) != '') $tags[]=$tag;
                }
                
                $url = getUrl('project',$this->id.'/view');
                $hash = sha1($url);
                
                request('bookmark','add',['url'=>$url,'comment'=>t('Project: ◊',$this->name),'tags'=>$tags]);
                
                $users = Project::connected_users(['ids'=>$this->id]);
                
                foreach ($users as $uid => $u){
                    if ($uid == $user->id) continue;
                    request('bookmark','index',['share_user_id'=>$uid,'share_url_hash'=>$hash,'notify'=>false]);
                }
            }
            
            return $this;
        }
        
        function send_note_notification(){
            global $user;
            $subject = t('◊ added a note.',$user->login);
            $text = t("Open the following site to see the note on \"◊\":\n\n◊",[$this->name,getUrl('project',$this->id.'/view')]);
            $recievers = [];
            foreach ($this->users as $u) {
                if ($u['data']['email'] == $user->email) continue;
                $recievers[] = $u['data']['email'];
            }
            send_mail($user->email, $recievers, $subject, $text);
            info('Sent email notification to users of this project.');
        }
    }
    
    class Settings {
        static function table(){
            return [
                'key'	=> ['VARCHAR'=>255,'KEY'=>'PRIMARY'],
                'value'	=> ['VARCHAR'=>255,'NOT NULL'],
            ];
        }
        
        static function db_version(){
            $db = get_or_create_db();
            $query = $db->prepare('SELECT value FROM settings WHERE key = "db_version"');
            if (!$query->execute()) throw new Exception(_('Failed to query db_version!'));
            $rows = $query->fetchAll(PDO::FETCH_COLUMN);
            if (empty($rows)) return null;
            return reset($rows);
        }
    }
