<?php

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
			$db->query('CREATE TABLE url_comments (url_hash VARCHAR(255) NOT NULL, comment_hash VARCHAR(255) NOT NULL, user_id INT NOT NULL, UNIQUE(url_hash, comment_hash, user_id));');
		} else {
			$db = new PDO('sqlite:db/tags.db');
		}
		return $db;
	}	

	function get_tag_list(){
		global $user;
		$db = get_or_create_db();
		$query = $db->prepare('SELECT * FROM tags WHERE user_id = :uid ORDER BY tag COLLATE NOCASE');
		assert($query->execute([':uid'=>$user->id]),'Was not able to request tag list!');
		return $query->fetchAll(INDEX_FETCH);
	}
	
	function get_new_urls($limit = null){
		global $user;
		$db = get_or_create_db();
		$sql = 'SELECT * FROM urls LEFT JOIN tags ON urls.hash = tags.url_hash WHERE user_id = :uid GROUP BY hash ORDER BY timestamp DESC';		
		$args = [':uid' => $user->id];
		if ($limit !== null){
			$sql .= ' LIMIT :limit';
			$args[':limit']=$limit;
		}
		$query = $db->prepare($sql);
		assert($query->execute($args),'Was not able to request url list!');
		$urls = $query->fetchAll(INDEX_FETCH);
		
		$hashes = array_keys($urls);
		$qmarks = implode(',', array_fill(0, count($hashes), '?'));
		
		$query = $db->prepare('SELECT tag,url_hash FROM tags WHERE url_hash IN ('.$qmarks.')');
		assert($query->execute($hashes),'Was not able to request tags for url list!');
		$tags = $query->fetchAll(PDO::FETCH_ASSOC);
		foreach ($tags as $tag) $urls[$tag['url_hash']]['tags'][] = $tag['tag'];
		
		$hashes[] = $user->id; // add us
		$query = $db->prepare('SELECT url_hash,comment FROM url_comments LEFT JOIN comments ON url_comments.comment_hash = comments.hash WHERE url_hash IN ('.$qmarks.') AND user_id = ?');
		assert($query->execute($hashes),'Was not able to request comments for url list!');
		$comments = $query->fetchAll(INDEX_FETCH);
		foreach ($comments as $url_hash => $comment) $urls[$url_hash]['comment'] = $comment['comment'];
		return $urls;
	}
	
	function save_tag($url = null,$tags = null,$comment = null, $redirect = true){
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
		
		if ($redirect)redirect(getUrl('bookmark'));
	}
	
	function load_tag($tag = null){
		global $user;
		assert($tag !== null,'Called load tag, but no tag given!');
		$db = get_or_create_db();
		
		$query = $db->prepare('SELECT * FROM tags WHERE user_id = :uid AND tag = :tag');
		assert($query->execute([':uid'=>$user->id,':tag'=>$tag]),'Was not able to laod tag form database!');
		$rows = $query->fetchAll(PDO::FETCH_ASSOC);
		
		if ($rows){
			$url_hashes = [];
			foreach ($rows as $row) $url_hashes[] = $row['url_hash'];
			
			$qMarks = str_repeat("?,", count($url_hashes)-1) . "?";
			
			$query = $db->prepare("SELECT * FROM urls WHERE hash IN ($qMarks)");
			assert($query->execute($url_hashes),'Was not able to load urls for tag!');
			$urls = $query->fetchAll(INDEX_FETCH);
			
			$query = $db->prepare("SELECT tag FROM tags WHERE url_hash = :hash");
			foreach ($url_hashes as $hash){
				$query->execute([':hash'=>$hash]);
				$tags = $query->fetchAll(INDEX_FETCH);
				foreach ($tags as $related => $dummy) {
					if ($related != $tag) $urls[$hash]['related'][] = $related;
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
	
	function load_url($hash){
		global $user;
		$db = get_or_create_db();
		$query = $db->prepare('SELECT * FROM urls WHERE hash = :hash;');
		$query->execute([':hash'=>$hash]);
		$url = $query->fetch(PDO::FETCH_ASSOC);
		
		$query = $db->prepare('SELECT comment FROM url_comments LEFT JOIN comments ON url_comments.comment_hash = comments.hash WHERE url_hash = :hash AND user_id = :uid;');
		$query->execute([':hash'=>$hash,':uid'=>$user->id]);
		$row = $query->fetch(PDO::FETCH_ASSOC);
		if ($row) $url['comment']= $row['comment'];
		
		$query = $db->prepare('SELECT tag FROM tags WHERE user_id = :uid AND url_hash = :hash ORDER BY TAG COLLATE NOCASE');
		$query->execute([':hash'=>$hash,':uid'=>$user->id]);
		$tags = $query->fetchAll(PDO::FETCH_ASSOC);
		foreach ($tags as $tag) $url['tags'][] = $tag['tag'];
		return $url;
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
		save_tag($_POST['url'],$_POST['tags'],$_POST['comment']);		
	}
?>
