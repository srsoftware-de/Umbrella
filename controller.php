<?php

	const MODULE = 'Bookmark';

	function get_or_create_db(){
		if (!file_exists('db')){
			assert(mkdir('db'),'Failed to create tag/db directory!');
		}
		assert(is_writable('db'),'Directory tag/db not writable!');
		if (!file_exists('db/tasks.db')){
			$db = new PDO('sqlite:db/tags.db');
			$db->query('CREATE TABLE tags (tag VARCHAR(255) NOT NULL, url_hash VARCHAR(255) NOT NULL, user_id int NOT NULL, UNIQUE(tag, url_hash, user_id));');			
			$db->query('CREATE TABLE urls (hash VARCHAR(255) PRIMARY KEY, url TEXT NOT NULL, timestamp INT NOT NULL DEFAULT 0);');
			$db->query('CREATE TABLE comments (hash VARCHAR(255) PRIMARY KEY, comment TEXT NOT NULL)');
			$db->query('CREATE TABLE url_comments (url_hash VARCHAR(255) NOT NULL, comment_hash VARCHAR(255) NOT NULL, user_id INT NOT NULL, UNIQUE(url_hash, user_id));');
		} else {
			$db = new PDO('sqlite:db/tags.db');
		}
		return $db;
	}
	
	class Bookmark{
		static function load($options){
			global $user;
			$sql = 'SELECT * FROM urls LEFT JOIN tags ON urls.hash = tags.url_hash';
			$where = ['user_id = ?'];
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
			
			if (!empty($where)) $sql .= ' WHERE '.implode(' AND ',$where);
			$sql .= ' GROUP BY hash';
				
			if (isset($options['order'])){
				$order = is_array($options['order']) ? $options['order'] : [$options['order']];
				$sql.= ' ORDER BY '.implode(', ',$order);
			}
			
			if (isset($options['limit'])){
				$sql.= ' LIMIT ?';
				$args[] = $options['limit'];
			}
			
			//debug(query_insert($sql,$args),1);
			$db = get_or_create_db();
			$query = $db->prepare($sql);
			//debug(query_insert($query,$args),1);
			assert($query->execute($args),'Was not able to request bookmark list!');
			$rows = $query->fetchAll(PDO::FETCH_ASSOC);
			$bookmarks = [];
			foreach ($rows as $row){
				$b = new Bookmark();
				unset($row['tag']);
				unset($row['hash']);
				$b->patch($row);
				unset($b->dirty);
				
				if ($single) return $b;
				$bookmarks[$row['url_hash']] = $b;
			} 
			return $bookmarks;
		}
		
		function comment(){
			if (empty($this->comment)) $this->comment = Comment::load(['url_hash'=>$this->url_hash]);
			return $this->comment;
		}
		
		function patch($data = array()){
			if (!isset($this->dirty)) $this->dirty = [];
			foreach ($data as $key => $val){
				if (!isset($this->{$key}) || $this->{$key} != $val) $this->dirty[] = $key;
				$this->{$key} = $val;
			}
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
				if (send_mail($user->email, $recipient['email'], t('? has shared a bookmark with you.',$user->login),t('You have been invited to have a look at ?. Visit ? to see all your bookmarks.',[$this->url,getUrl('bookmark')]))){
					info('Notification has been sent to user.');
				}
			}
		}
		
		function tags(){
			if (empty($this->tags)) $this->tags = Tag::load([ 'url_hash' => $this->url_hash, 'order'=>'tag ASC']);
			return $this->tags;
		}
	}
	
	class Comment{
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

				
			if (!empty($where)) $sql .= ' WHERE '.implode(' AND ',$where);
			
			$db = get_or_create_db();
			$query = $db->prepare($sql);
			assert($query->execute($args),'Was not able to request comment list!');
			
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
			return $comments;
		}
		
		function assign($user_id){
			$db = get_or_create_db();
			$query = $db->prepare('INSERT OR IGNORE INTO url_comments (url_hash, comment_hash, user_id) VALUES (:url_hash, :comment_hash, :uid)');
			$query->execute([':url_hash'=>$this->url_hash,':comment_hash'=>$this->comment_hash,':uid'=>$user_id]);
		}
		
		function patch($data = array()){
			if (!isset($this->dirty)) $this->dirty = [];
			foreach ($data as $key => $val){
				if (!isset($this->{$key}) || $this->{$key} != $val) $this->dirty[] = $key;
				$this->{$key} = $val;
			}
			return $this;
		}
	}
	
	class Tag{
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
			
			if (!empty($where)) $sql .= ' WHERE '.implode(' AND ',$where);
			
			$sql .= ' COLLATE NOCASE';
				
			if (isset($options['order'])){
				$order = is_array($options['order']) ? $options['order'] : [$options['order']];
				$sql.= ' ORDER BY '.implode(', ',$order);
			}
			
			$db = get_or_create_db();
			$query = $db->prepare($sql);
			
			assert($query->execute($args),'Was not able to request tag list!');
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
			if (empty($this->bookmarks)) $this->bookmarks = Bookmark::load(['url_hash'=>$this->url_hashes]);
			return $this->bookmarks;
		}
		
		function patch($data = array()){
			if (!isset($this->dirty)) $this->dirty = [];
			foreach ($data as $key => $val){
				if (!isset($this->{$key}) || $this->{$key} != $val) $this->dirty[] = $key;
				$this->{$key} = $val;
			}
			return $this;
		}
		
		function save(){
			$db = get_or_create_db();
			$query = $db->prepare('INSERT OR IGNORE INTO tags (tag, url_hash, user_id) VALUES (:tag, :hash, :uid)');
			foreach ($this->url_hashes as $hash) $query->execute([':tag'=>$this->tag,':hash'=>$hash,':uid'=>$this->user_id]);
		}
	}
	
	function save_tag($url = null,$tags = null,$comment = null){
		global $user;
		assert($url !== null && $url != '','No value set for url!');
		assert($tags !== null,'No tags set');
		if (!is_array($tags)) $tags = explode(' ',str_replace(',', ' ', $tags));
		$url_hash = sha1($url);
		
		$comment_hash = ($comment !== null && $comment != '') ? sha1($comment) : null;
		
		$db = get_or_create_db();
		$query = $db->prepare('INSERT OR IGNORE INTO urls (hash, url, timestamp) VALUES (:hash, :url, :time);');
		assert($query->execute([':hash'=>$url_hash,':url'=>$url,':time'=>time()]),'Was not able to store url in database');
		
		if ($comment_hash !== null) {
			$query = $db->prepare('INSERT OR IGNORE INTO comments (hash, comment) VALUES (:hash, :comment);');
			assert($query->execute([':hash'=>$comment_hash,':comment'=>$comment]));
				
			$query = $db->prepare('INSERT OR IGNORE INTO url_comments (url_hash, comment_hash, user_id) VALUES (:uhash, :chash, :uid)');
			$query->execute([':uhash'=>$url_hash,':chash'=>$comment_hash,':uid'=>$user->id]); 
		}
		
		$query = $db->prepare('INSERT OR IGNORE INTO tags (tag, url_hash, user_id) VALUES (:tag, :hash, :uid);');
		foreach ($tags as $tag) {
			if ($tag != '')	assert($query->execute([':tag'=>strtolower($tag),':hash'=>$url_hash,':uid'=>$user->id]),'Was not able to save tag '.$tag);		
		}
		return $tag;
	}
	
	function load_tag($tag = null){
		global $services,$user;
		assert($tag !== null,'Called load tag, but no tag given!');
		$db = get_or_create_db();
		
		$query = $db->prepare('SELECT * FROM tags WHERE user_id = :uid AND tag = :tag');
		assert($query->execute([':uid'=>$user->id,':tag'=>$tag]),'Was not able to laod tag form database!');
		$rows = $query->fetchAll(PDO::FETCH_ASSOC);
		
		if ($rows){
			$url_hashes = [];
			foreach ($rows as $row) $url_hashes[] = $row['url_hash'];
			
			$qMarks = str_repeat("?,", count($url_hashes)-1) . "?";
			
			$query = $db->prepare("SELECT * FROM urls WHERE hash IN ($qMarks) ORDER BY timestamp DESC");
			assert($query->execute($url_hashes),'Was not able to load urls for tag!');
			$urls = $query->fetchAll(INDEX_FETCH);
			
			$query = $db->prepare("SELECT tag FROM tags WHERE url_hash = :hash");
			foreach ($urls as $hash => &$url){
				$query->execute([':hash'=>$hash]);
				$tags = $query->fetchAll(INDEX_FETCH);
				foreach ($tags as $related => $dummy) {
					if ($related != $tag) $url['tags'][] = $related;
				}
				$url['external']=true;
				foreach ($services as $name => $service){
					if (strpos($url['url'],$service['path']) === 0) $url['external'] = false;
				}	
			}

			$query = $db->prepare("SELECT url_hash, comment_hash FROM url_comments WHERE user_id = ? AND url_hash IN ($qMarks)");
			array_unshift($url_hashes, $user->id);
			assert($query->execute($url_hashes),'Was not able to load urls for tag!');
			$rows = $query->fetchAll(INDEX_FETCH);
			if ($rows){
				$qMarks = str_repeat("?,", count($rows)-1) . "?";
				$query = $db->prepare("SELECT * FROM comments WHERE hash = :hash");
				foreach ($rows as $url_hash => $row){
					$query->execute([':hash'=>$row['comment_hash']]);
					$comment = $query->fetch(PDO::FETCH_ASSOC);
					$urls[$url_hash]['comment'] = $comment['comment'];
				}				
			}

		}
		$tag = objectFrom(['tag'=>$tag]);
		$tag->links = $urls;
		return $tag;
	}
	
	function load_url($hash,$load_details = true){
		global $user;
		$db = get_or_create_db();
		$query = $db->prepare('SELECT * FROM urls WHERE hash = :hash;');
		$query->execute([':hash'=>$hash]);
		$url = $query->fetch(PDO::FETCH_ASSOC);

		if ($load_details){
			$query = $db->prepare('SELECT comment FROM url_comments LEFT JOIN comments ON url_comments.comment_hash = comments.hash WHERE url_hash = :hash AND user_id = :uid;');
			$query->execute([':hash'=>$hash,':uid'=>$user->id]);
			$row = $query->fetch(PDO::FETCH_ASSOC);
			if ($row) $url['comment']= $row['comment'];
	
			$query = $db->prepare('SELECT tag FROM tags WHERE user_id = :uid AND url_hash = :hash ORDER BY TAG COLLATE NOCASE');
			$query->execute([':hash'=>$hash,':uid'=>$user->id]);
			$tags = $query->fetchAll(PDO::FETCH_ASSOC);
			foreach ($tags as $tag) $url['tags'][] = $tag['tag'];
		}
		return $url;
	}
	
	function search_bookmarks($key){
		global $user;
		$key = '%'.$key.'%';
		$db = get_or_create_db();
		$query = $db->prepare('SELECT tag FROM tags WHERE user_id = :uid AND tag LIKE :key GROUP BY tag ORDER BY tag');
		$query->execute([':uid'=>$user->id, ':key'=>$key]);
		$tags = array_keys($query->fetchAll(INDEX_FETCH));
				
		$query = $db->prepare('SELECT * FROM comments LEFT JOIN url_comments ON comments.hash = url_comments.comment_hash LEFT JOIN urls ON urls.hash = url_comments.url_hash WHERE (comment LIKE :key OR url LIKE :key) AND user_id = :uid');
		$query->execute([':uid'=>$user->id, ':key'=>$key]);
		$raw = $query->fetchAll(PDO::FETCH_ASSOC);
		
		$query = $db->prepare('SELECT * FROM tags WHERE url_hash = :hash AND user_id = :uid');
		$urls = [];
		foreach ($raw as $entry){
			$hash = $entry['url_hash'];
			$query->execute([':hash'=>$hash,':uid'=>$user->id]);
			$urls[$hash] = [
				'url' => $entry['url'],
				'comment' => $entry['comment'],
				'tags' => array_keys($query->fetchAll(INDEX_FETCH)),
			];			
		}
		return [ 'tags' => $tags, 'urls' => $urls ];
	}

	function delete_link($link){
		global $user;
		$url_hash = sha1($link['url']);
		$db = get_or_create_db();
		$query = $db->prepare('DELETE FROM tags WHERE url_hash = :hash AND user_id = :uid;');
		$query->execute([':hash'=>$url_hash,':uid'=>$user->id]);

		$query = $db->prepare('DELETE FROM url_comments WHERE url_hash = :hash AND user_id = :uid;');
		$query->execute([':hash'=>$url_hash,':uid'=>$user->id]);
	}

	function update_url($link){
		delete_link($link);
		$tag = save_tag(param('url'),param('tags'),param('comment'));
		info('Bookmark has been updated.');
		return $tag;
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
?>
