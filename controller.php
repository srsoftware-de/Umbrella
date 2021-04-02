<?php include '../bootstrap.php';
	const MODULE = 'Bookmark';
	const NO_SHARE=0;
	const SHARE_AND_NOTIFY=1;
	const SHARE_DONT_NOTIFY=2;

	$title = 'Umbrella Bookmark Management';

	function get_or_create_db(){
		$table_filename = 'tags.db';
		if (!file_exists('.db') && !mkdir('.db')) throw new Exception('Failed to create '.strtolower(MODULE).'/.db directory!');
		if (!is_writable('.db')) throw new Exception('Directory '.strtolower(MODULE).'/.db not writable!');
		if (!file_exists('.db/'.$table_filename)){
			$db = new PDO('sqlite:.db/'.$table_filename);

			$tables = [
				'tags'=>Tag::table(),
				'urls'=>Bookmark::table(),
				'comments'=>Comment::table(),
				'url_comments'=>Bookmark::url_table(),
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
				$query = $db->prepare($sql);
				if (!$query->execute()) throw new Exception('Was not able to create '.$table.' table in '.$table_filename.'!');
			}
		} else {
			$db = new PDO('sqlite:.db/'.$table_filename);
		}
		return $db;
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


	class Bookmark extends UmbrellaObject{
		static function table(){
			return [
				'hash'				=> ['VARCHAR'=>255,'KEY'=>'PRIMARY'],
				'url'				=> ['TEXT','NOT NULL'],
				'timestamp'			=> ['INT','NOT NULL','DEFAULT 0'],
			];
		}

		static function url_table(){
			return [
				'url_hash'			=> ['VARCHAR'=>255,'NOT NULL'],
				'comment_hash'		=> ['VARCHAR'=>255,'NOT NULL'],
				'user_id'			=> ['INT','NOT NULL'],
				'UNIQUE'			=> ['url_hash','user_id'],
			];
		}

		static function add($url,$tags_string,$comment_text){
			global $user;

			$bookmark = (new Bookmark())->patch(['url'=>$url])->save();

			$comment = new Comment();
			$comment->patch(['comment'=>$comment_text,'url_hash'=>$bookmark->url_hash])->save()->assign($user->id);

			$tags = is_array($tags_string) ? $tags_string : explode(' ',str_replace(',', ' ', $tags_string));
			foreach ($tags as $tag) (new Tag())->patch(['tag'=>$tag,'url_hashes'=>[$bookmark->url_hash],'user_id'=>$user->id])->save();
			return $bookmark;
		}

		static function load($options){
			global $services,$user;
			$sql = 'SELECT * FROM urls LEFT JOIN tags ON urls.hash = tags.url_hash';
			$where = ['tags.user_id = ?'];
			$having = [];
			$args = [$user->id];
			$single = false;
			if (isset($options['url_hash'])){
				if (!is_array($options['url_hash'])){
					$options['url_hash'] = [$options['url_hash']];
					$single = true;
				}
				$where[] = 'url_hash IN ('.implode(',', array_fill(0, count($options['url_hash']), '?')).')';
				$args = array_merge($args,$options['url_hash']);
			}
			if (isset($options['search'])){
				$sql = str_replace("*", "url,timestamp,url_comments.url_hash,url_comments.user_id,(GROUP_CONCAT(tag) || ',' || comment || ',' || url ) AS search", $sql); // add a field that contains url, comment and all tags
				$sql.= ' LEFT JOIN url_comments ON urls.hash = url_comments.url_hash LEFT JOIN comments ON url_comments.comment_hash = comments.hash'; // join required tables
				$where[] = 'url_comments.user_id = ?'; // only fetch comments of current user
				$args[] = $user->id;

				$keys = explode('+',$options['search']); // search in concatenated field
				foreach ($keys as $key){
					$having[] = 'search LIKE ?';
					$args[] = '%'.trim($key).'%';
				}
			}

			if (!empty($where)) $sql .= ' WHERE '.implode(' AND ',$where);
			$sql .= ' GROUP BY urls.hash'; // some queries load tables that also contain hash column
			if (!empty($having)) $sql .= ' HAVING '.implode(' AND ',$having);

			if (isset($options['order'])){
				$order = is_array($options['order']) ? $options['order'] : [$options['order']];
				$sql.= ' ORDER BY '.implode(', ',$order);
			}

			if (isset($options['limit'])){
				$sql.= ' LIMIT ?';
				$args[] = $options['limit'];
			}

			//debug(query_insert($sql,$args));
			$db = get_or_create_db();
			$query = $db->prepare($sql);
			//debug(query_insert($query,$args));
			if (!$query->execute($args)) throw new Exception('Was not able to request bookmark list!');
			$rows = $query->fetchAll(PDO::FETCH_ASSOC);
			//debug($rows,1);
			$bookmarks = [];
			foreach ($rows as $row){
				$b = new Bookmark();
				unset($row['tag']);
				unset($row['hash']);
				$b->patch($row);

				foreach ($services as $name => $service){
					if (strpos($b->url,$service['path']) === 0) $b->internal = $name;
				}

				unset($b->dirty);

				if ($single) return $b;
				$bookmarks[$row['url_hash']] = $b;
			}
			if ($single) return null;
			return $bookmarks;
		}

		function comment(){
			if (empty($this->comment)) $this->comment = Comment::load(['url_hash'=>$this->url_hash]);
			return $this->comment;
		}

		function delete(){
			global $user;
			$db = get_or_create_db();
			$query = $db->prepare('DELETE FROM tags WHERE url_hash = :hash AND user_id = :uid;');
			$query->execute([':hash'=>$this->url_hash,':uid'=>$user->id]);

			$query = $db->prepare('DELETE FROM url_comments WHERE url_hash = :hash AND user_id = :uid;');
			$query->execute([':hash'=>$this->url_hash,':uid'=>$user->id]);
		}

		function json(){
			return json_encode([
					'hash'=>$this->url_hash,
					'url'=>$this->url,
					'timestamp'=>$this->timestamp,
					'comment'=>$this->comment()->comment,
					'tags'=>array_keys($this->tags())]);
		}

		function save(){
			$this->patch(['url_hash'=>sha1($this->url)]);
			$db = get_or_create_db();
			$query = $db->prepare('INSERT OR IGNORE INTO urls (hash, url, timestamp) VALUES (:hash, :url, :time );');
			$args = [':hash'=>$this->url_hash,':url'=>$this->url,':time'=>time()];
			if (!$query->execute($args)) throw new Exception('Was not able to store url in database');
			unset($this->dirty);
			return $this;
		}

		function share($user_id, $notify = true){
			global $user;
			foreach ($this->tags() as $tag){
				$tag->patch(['user_id'=>$user_id]);
				$tag->save();
			}
			$this->comment()->assign($user_id);

			info('Your bookmark has been shared.');
			if ($notify){
				$recipient = request('user','json',['ids'=>$user_id]);
				if (send_mail($user->email, $recipient['email'], t('◊ has shared a bookmark with you.',$user->login),t('You have been invited to have a look at ◊. Visit ◊ to see all your bookmarks.',[$this->url,getUrl('bookmark')]))){
					info('Notification has been sent to user.');
				}
			}
		}

		function tags(){
			if (empty($this->tags)) $this->tags = Tag::load([ 'url_hash' => $this->url_hash, 'order'=>'tag ASC']);
			return $this->tags;
		}

		function update($url,$tags_string,$comment_text){
			global $user;
			$this->delete();
			Bookmark::add($url, $tags_string, $comment_text);
		}
	}

	class Comment extends UmbrellaObject{
		static function table(){
			return [
				'hash'				=> ['VARCHAR'=>255,'KEY'=>'PRIMARY'],
				'comment'			=> ['TEXT','NOT NULL'],
			];
		}

		static function load($options){
			global $user;
			$sql = 'SELECT * FROM url_comments LEFT JOIN comments ON url_comments.comment_hash = comments.hash';
			$where = ['user_id = ?'];
			$args =  [ $user->id ];

			$single = false;
			if (isset($options['url_hash'])){
				if (!is_array($options['url_hash'])){
					$options['url_hash'] = [$options['url_hash']];
					$single = true;
				}
				$where[] = 'url_hash IN ('.implode(',', array_fill(0, count($options['url_hash']), '?')).')';
				$args = array_merge($args,$options['url_hash']);
			}

			if (isset($options['search'])){
				$search = $options['search'];
				$search = explode(' ',$search);
				foreach ($search as $key){
					$where[] = 'comment LIKE ?';
					$args[] = '%'.trim($key).'%';
				}
			}

			if (!empty($where)) $sql .= ' WHERE '.implode(' AND ',$where);

			$db = get_or_create_db();
			$query = $db->prepare($sql);
			//debug(query_insert($query,$args),1);
			if (!$query->execute($args)) throw new Exception('Was not able to request comment list!');

			$rows = $query->fetchAll(PDO::FETCH_ASSOC);
			$comments = [];
			foreach ($rows as $row){
				$hash = $row['comment_hash'];
				unset($row['hash']);
				$c = new Comment();
				$c->patch($row);
				unset($c->dirty);
				if ($single) return $c;
				$comments[$hash] = $c;
			}
			if ($single) return null;
			return $comments;
		}

		function assign($user_id){
			$db = get_or_create_db();
			$args = [':url_hash'=>$this->url_hash,':uid'=>$user_id];
			$query = $db->prepare('DELETE FROM url_comments WHERE url_hash = :url_hash AND user_id = :uid');
			$query->execute($args);
			$query = $db->prepare('INSERT OR IGNORE INTO url_comments (url_hash, comment_hash, user_id) VALUES (:url_hash, :comment_hash, :uid)');
			$args[':comment_hash']=$this->comment_hash;
			$query->execute($args);
			unset($this->dirty);
			return $this;
		}

		function save(){
			if (empty($this->comment)) throw new Exception(t('Comment must not be empty'));

			$this->patch(['comment_hash'=>sha1($this->comment)]);

			$db = get_or_create_db();
			$query = $db->prepare('INSERT OR IGNORE INTO comments (hash, comment) VALUES (:hash, :comment );');
			$args = [':hash'=>$this->comment_hash,':comment'=>$this->comment];
			if (!$query->execute($args)) throw new Exception('Was not able to save comment!');
			unset($this->dirty);
			return $this;
		}
	}

	class Tag extends UmbrellaObject{
		static function table(){
			return [
				'tag'				=> ['VARCHAR'=>255,'NOT NULL'],
				'url_hash'			=> ['VARCHAR'=>255,'NOT NULL'],
				'user_id'			=> ['INT','NOT NULL'],
				'UNIQUE'			=> ['tag', 'url_hash', 'user_id'],
			];
		}

		static function load($options){
			global $user;
			$sql = 'SELECT * FROM tags';

			$where = ['user_id = ?'];
			$args =  [ $user->id ];
			$single = false;
			if (isset($options['url_hash'])){
				$hashes = is_array($options['url_hash']) ? $options['url_hash'] : [$options['url_hash']];
				$where[] = 'url_hash IN ('.implode(',', array_fill(0, count($hashes), '?')).')';
				$args = array_merge($args,$hashes);
			}

			if (isset($options['tag'])){
				$where[] = 'tag = ?';
				$args[] = $options['tag'];
				$single = true;
			}

			if (isset($options['search'])){
				$keys = explode('+',$options['search']);
				foreach ($keys as $key){
					$where[] = 'tag LIKE ?';
					$args[] = '%'.$key.'%';
				}
			}

			if (!empty($where)) $sql .= ' WHERE '.implode(' AND ',$where);

			$sql .= ' COLLATE NOCASE';

			if (isset($options['order'])){
				$order = is_array($options['order']) ? $options['order'] : [$options['order']];
				$sql.= ' ORDER BY '.implode(', ',$order);
			}

			$db = get_or_create_db();
			$query = $db->prepare($sql);
			if (!$query->execute($args)) throw new Exception('Was not able to request tag list!');
			$rows = $query->fetchAll(PDO::FETCH_ASSOC);
			$tags = [];

			foreach ($rows as $row){
				$tag = $row['tag'];
				if (empty($tags[$tag])){
					$t = new Tag();
					$t->url_hashes = [$row['url_hash']];
					unset($row['url_hash']);
					$t->patch($row);
					unset($t->dirty);
					$tags[$tag] = $t;
				} else $tags[$tag]->url_hashes[] = $row['url_hash'];
			}
			if ($single) return reset($tags);
			return $tags;
		}

		function bookmarks(){
			if (empty($this->bookmarks)) $this->bookmarks = Bookmark::load(['url_hash'=>$this->url_hashes,'order'=>'timestamp DESC']);
			return $this->bookmarks;
		}

		function save(){
			$db = get_or_create_db();
			$query = $db->prepare('INSERT OR IGNORE INTO tags (tag, url_hash, user_id) VALUES (:tag, :hash, :uid)');
			foreach ($this->url_hashes as $hash) {
				$args = [':tag'=>strtolower($this->tag),':hash'=>$hash,':uid'=>$this->user_id];
				$query->execute($args);
			}
			unset($this->dirty);
			return $this;
		}
	}
?>
