<?php 	include '../bootstrap.php';

	const MODULE = 'Wiki';
	$title = t('Umbrella Wiki');

	function get_or_create_db(){
		$table_filename = 'wiki.db';
		if (!file_exists('db')) assert(mkdir('db'),'Failed to create '.strtolower(MODULE).'/db directory!');
		assert(is_writable('db'),'Directory '.strtolower(MODULE).'/db not writable!');
		if (!file_exists('db/'.$table_filename)){
			$db = new PDO('sqlite:db/'.$table_filename);

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
									assert($prop_v === 'PRIMARY','Non-primary keys not implemented in '.strtolower(MODULE).'/controller.php!');
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
				assert($db->query($sql),'Was not able to create '.$table.' table in '.$table_filename.' ("'.$sql.'") !');
			}
		} else {
			$db = new PDO('sqlite:db/'.$table_filename);
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

		function grant_access($user_rights){
			$db = get_or_create_db();
			$db->beginTransaction();
			$sql = 'DELETE FROM page_users WHERE page_id = :pid';
			$query = $db->prepare($sql);
			$args = [':pid'=>$this->id];
			if (!$query->execute($args)) {
				$db->rollBack();
				throw new Exception('Was not able to grant permissions for page!');
			}

			$users_ids = [];
			$sql = 'INSERT INTO page_users (page_id, user_id, permissions) VALUES (:pid, :usr, :perm)';
			$query = $db->prepare($sql);
			foreach ($user_rights as $user_id => $perm){
				if ($perm == 0) continue;
				$user_ids[] = $user_id;
				$args[':usr']  = $user_id;
				$args[':perm'] = $perm;
				if (!$query->execute($args)) {
					$db->rollBack();
					throw new Exception('Was not able to grant permissions for page!');
				}
			}
			$db->commit();
			return $user_ids;
		}

		function load($options = []){
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

			$sql .= ' ORDER BY version ASC, id ASC'; // this, together with INDEX_FETCH assures that only the last version is loaded

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
			$content = preg_replace('/====(.*)====/', '#### $1', $content);
			$content = preg_replace('/===(.*)===/', '### $1', $content);
			$content = preg_replace('/==(.*)==/', '## $1', $content);
			$content = preg_replace('/=(.*)=/', '# $1', $content);

			// replace mediawiki-style lists:
			$content = preg_replace('/^\s*\*\*/m', '    *', $content);

			//debug($content,1);
			return $content;
		}

		function update($new_content){
			global $user;

			$new_content = Page::convertMediaWiki($new_content);
			$sql = 'INSERT INTO pages (id, version, content) VALUES (:id, :vers, :content )';
			$args = [':id'=>$this->id,':content'=>$new_content,':vers'=>$this->version+1];

			$db = get_or_create_db();
			$query = $db->prepare($sql);
			//debug(query_insert($query, $args),1);
			if (!$query->execute($args)) throw new Exception('Was not able to update page!');
			return $this->id.'/share';
		}

		function users(){
			if (!isset($this->users)){
				$db = get_or_create_db();
				$sql = 'SELECT * FROM page_users WHERE page_id = :id';
				$args = [':id'=>$this->id];

				$query = $db->prepare($sql);
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
	}

?>
