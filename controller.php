<?php 	include '../bootstrap.php';

	const MODULE = 'Wiki';
	$title = t('Umbrella Wiki');
	$base_url = getUrl('wiki');

	function get_or_create_db(){
		$table_filename = 'wiki.db';
		if (!file_exists('.db')) if (!mkdir('.db')) throw new Exception('Failed to create '.strtolower(MODULE).'/.db directory!');
		if (!is_writable('.db')) throw new Exception('Directory '.strtolower(MODULE).'/.db not writable!');
		if (!file_exists('.db/'.$table_filename)){
			$db = new PDO('sqlite:.db/'.$table_filename);

			$tables = [
					'pages'=>Page::table(),
					'page_users'=>Page::user_table(),
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
									if ($prop_v != 'PRIMARY') throw new Exception('Non-primary keys not implemented in '.strtolower(MODULE).'/controller.php!');
									$sql.= 'PRIMARY KEY '; break;
								default:
									$sql .= $prop_v.' ';
							}
						}
						$sql .= ", ";
					} else $sql .= $props.", ";
				}
				$sql = str_replace([' ,',', )'],[',',')'],$sql.')');
				if (!$db->query($sql)) throw new Exception('Was not able to create '.$table.' table in '.$table_filename.' ("'.$sql.'") !');
			}
		} else {
			$db = new PDO('sqlite:.db/'.$table_filename);
		}
		return $db;
	}

	class Page extends UmbrellaObject{
		const READ  = 0b01;
		const WRITE = 0b10;

		static function table(){
			return [
				'id'				=> ['VARCHAR'=>255,'NOT NULL'],
				'version'			=> ['INT','NOT NULL'],
				'content'			=> ['TEXT','NOT NULL'],
				'PRIMARY KEY'		=> ['id','version'],
			];
		}

		static function user_table(){
			return [
				'page_id'			=> ['VARCHAR'=>255,'NOT NULL'],
				'user_id'			=> ['INT','NOT NULL'],
				'permissions'		=> ['INT','NOT NULL'],
				'PRIMARY KEY'		=> ['page_id','user_id'],
			];
		}

		function delete_version(){
			$sql = 'DELETE FROM pages WHERE id = :id AND version = :version';
			$args = [':id'=>$this->id,':version'=>$this->version];
			$query = get_or_create_db()->prepare($sql);
			if (!$query->execute($args)) throw new Exception('Was not able to remove version '.$this->version.' of '.$this->id);
		}

		function grant_access($user_rights){
			global $user;
			$db = get_or_create_db();

			$old_users = $this->users();
			$db->beginTransaction();
			$sql = 'DELETE FROM page_users WHERE page_id = :pid';
			$query = $db->prepare($sql);
			$args = [':pid'=>$this->id];
			if (!$query->execute($args)) {
				$db->rollBack();
				throw new Exception('Was not able to grant permissions for page!');
			}

			$added_user_ids = [];
			$sql = 'INSERT INTO page_users (page_id, user_id, permissions) VALUES (:pid, :usr, :perm)';
			$query = $db->prepare($sql);
			foreach ($user_rights as $user_id => $perm){
				if ($perm == 0) continue;
				if (!array_key_exists($user_id, $old_users)) $added_user_ids[] = $user_id;
				$args[':usr']  = $user_id;
				$args[':perm'] = $perm;
				if (!$query->execute($args)) {
					$db->rollBack();
					throw new Exception('Was not able to grant permissions for page!');
				}
			}
			$db->commit();

			if (param('notify') == 'on'){
				$subject = t('◊ shared a wiki page with you',$user->login);
				$path = str_replace(" ", "%20", $this->id).'/view';
				$text = t('The page ◊ has been shared with you:','['.$this->id.']('.getUrl('wiki',$path).')')." \n\n".$this->content;
				$message = ['subject'=>$subject,'body'=>$text,'recipients'=>$added_user_ids];
				request('user','notify',$message);
				info('User(s) have been notified.');
			}
		}

		static function load($options = []){
			global $user;

			$single = false;
			$sql = 'SELECT * FROM pages';
			$where = [];//['user_id = ?'];
			$args = [];//[$user->id];

			if (isset($options['user_id'])){
				$sql .= ' LEFT JOIN page_users ON pages.id = page_id';
				$where[] = 'user_id = ? OR user_id = 0';
				$where[] = 'permissions > 0';
				$args[] = $options['user_id'];
			}

			if (isset($options['ids'])){
				$ids = $options['ids'];
				if (!is_array($ids) && $ids = [$ids]) $single = true;
				$qMarks = str_repeat('?,', count($ids)-1).'?';
				$where[] = 'id IN ('.$qMarks.')';
				$args = array_merge($args, $ids);
			}

			if (isset($options['key'])){
				$key = '%'.$options['key'].'%';
				$where[] = 'id LIKE ? OR content LIKE ?';
				$args = array_merge($args, [$key,$key]);
			}

			if (isset($options['version'])){
				$where[] = 'version = ?';
				$args[] = $options['version'];
			}

			if (!empty($where)) $sql .= ' WHERE ('.implode(') AND (', $where).')';

			$order = empty($options['order'])?'default':$options['order'];

			$sql .= ' ORDER BY id COLLATE NOCASE ASC, version ASC'; // this, together with INDEX_FETCH assures that only the last version is loaded

			$db = get_or_create_db();
			$query = $db->prepare($sql);
			//debug(query_insert($query, $args),1);
			if (!$query->execute($args)) throw new Exception('Was not able to load pages!');

			$result = [];
			$rows = $query->fetchAll(INDEX_FETCH);
			//debug(['rows'=>$rows,'single'=>$single,'query'=>query_insert($query, $args)]);
			foreach ($rows as $id => $row){
				$page = new Page();
				$page->patch($row)->patch(['id'=>$id]);
				unset($page->dirty);
				if ($single) return $page;
				$result[$id] = $page;
			}
			if ($single) return null;
			return $result;
		}

		function rename($new_name){
			$db = get_or_create_db();
			$args = [':new'=>$new_name,':old'=>$this->id];
			$db->beginTransaction();
			$sql = 'UPDATE pages SET id = :new WHERE id = :old ';
			$query = $db->prepare($sql);
			if (!$query->execute($args)){
				error('Was not able to rename page ◊ to ◊',[$this->id,$new_name]);
				$db->rollBack();
				return null;
			}

			$sql = 'UPDATE page_users SET page_id = :new WHERE page_id = :old';
			$query = $db->prepare($sql);
			if (!$query->execute($args)){
				error('Was not able to rename page ◊ to ◊',[$this->id,$new_name]);
				$db->rollBack();
				return null;
			}
			$db->commit();

			// TODO: update bookmarks and notes

			$subject = t('The page "◊" has been renamed to "◊".',[$this->id,$new_name]);
			info($subject.' '.t('<a href="share">Click here</a> to share it with other users.'));
			if (param('notify') == 'on'){
				$recipients = array_keys($this->users());
				$body = t('Content of ◊:',getUrl('wiki',$new_name.'/view'))."\n\n".$this->content;
				request('user','notify',['recipients'=>$recipients,'subject'=>$subject,'body'=>$body]);
				info('User(s) have been notified.');
			}

			return $new_name.'/view';
		}

		function save(){
			global $user;

			$sql = 'INSERT INTO pages (id, version, content) VALUES (:id, 1, :content )';
			$args = [':id'=>$this->id,':content'=>Page::convertMediaWiki($this->content)];
			$db = get_or_create_db();
			$query = $db->prepare($sql);
			if (!$query->execute($args)) throw new Exception('Was not able to store new page!');

			$this->grant_access([$user->id => (Page::READ | Page::WRITE)]);
		}

		function setTags($raw_tags){
			$raw_tags = explode(' ', str_replace(',',' ',$raw_tags));
			$tags = [];
			foreach ($raw_tags as $tag){
				if (trim($tag) != '') $tags[]=$tag;
			}
			$url = getUrl('wiki',$this->id.'/view');
			request('bookmark','add',['url'=>$url,'comment'=>$this->id,'tags'=>$tags]);
		}

		static function convertMediaWiki($content){
			$content = preg_replace('/\[\[([^\]]*)\]\]/', '[$1](../$1/view)', $content); // replace mediawiki-style links

			// replace mediawiki-style headings
			$content = preg_replace("/\n====(.*)====/", '#### $1', $content);
			$content = preg_replace("/\n===(.*)===/", '### $1', $content);
			$content = preg_replace("/\n==(.*)==/", '## $1', $content);
			$content = preg_replace("/\n=(.*)=/", '# $1', $content);

			// replace mediawiki-style lists:
			$content = preg_replace('/^\s*\*\*/m', '    *', $content);

			//debug($content,1);
			return $content;
		}

		function update($new_content){
			$new_content = Page::convertMediaWiki($new_content);
			$sql = 'INSERT INTO pages (id, version, content) VALUES (:id, :vers, :content )';
			$args = [':id'=>$this->id,':content'=>$new_content,':vers'=>$this->version+1];

			$db = get_or_create_db();
			$query = $db->prepare($sql);
			//debug(query_insert($query, $args),1);
			if (!$query->execute($args)) throw new Exception('Was not able to update page!');
			$subject = t('The page "◊" has been updated.',$this->id);
			info($subject.' – '.t('<a href="share">Click here</a> to share it with other users.'));
			if (param('notify') == 'on'){
				$recipients = array_keys($this->users());
				$body = t('New content of ◊:',getUrl('wiki',$this->id.'/view'))."\n\n".$new_content;
				request('user','notify',['recipients'=>$recipients,'subject'=>$subject,'body'=>$body]);
				info('User(s) have been notified.');
			}
			return $this->id.'/view';
		}

		function users(){
			if (!isset($this->users)){
				$sql = 'SELECT * FROM page_users WHERE page_id = :id';
				$args = [':id'=>$this->id];

				$query = get_or_create_db()->prepare($sql);
				//debug(query_insert($query, $args));
				if (!$query->execute($args)) throw new Exception('Could not load users of page');
				$rows = $query->fetchAll(PDO::FETCH_ASSOC);
				$this->users = [];
				foreach ($rows as $row) $this->users[$row['user_id']] = $row['permissions'];

				$users = request('user','json',['ids'=>array_keys($this->users)]);
				foreach ($users as $user_id => $user){
					$user['perms'] = $this->users[$user_id];
					$this->users[$user_id] = $user;
				}
			}
			return $this->users;
		}

		function versions(){
			if (empty($this->versions)){
				$sql = 'SELECT version FROM pages WHERE id = :id ORDER BY version DESC';
				$args = [':id'=>$this->id];
				$query = get_or_create_db()->prepare($sql);
				//debug(query_insert($query, $args));
				if (!$query->execute($args)) throw new Exception('Was not able to load version list of '.$this->id);
				$rows = $query->fetchAll(INDEX_FETCH);
				$this->versions = array_keys($rows);
			}
			return $this->versions;
		}
	}

?>
