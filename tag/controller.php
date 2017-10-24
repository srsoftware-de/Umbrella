<?php

	function get_or_create_db(){
		if (!file_exists('db')){
			assert(mkdir('db'),'Failed to create tag/db directory!');
		}
		assert(is_writable('db'),'Directory tag/db not writable!');
		if (!file_exists('db/tasks.db')){
			$db = new PDO('sqlite:db/tags.db');
			$db->query('CREATE TABLE tags (tag VARCHAR(255) NOT NULL, url_hash VARCHAR(255) NOT NULL, user_id int NOT NULL, UNIQUE(tag, url_hash, user_id));');			
			$db->query('CREATE TABLE urls (hash VARCHAR(255) PRIMARY KEY, url TEXT NOT NULL);');
			$db->query('CREATE TABLE comments (hash VARCHAR(255) PRIMARY KEY, comment TEXT NOT NULL)');
			$db->query('CREATE TABLE url_comments (url_hash VARCHAR(255) NOT NULL, comment_hash VARCHAR(255) NOT NULL, user_id INT NOT NULL, UNIQUE(url_hash, comment_hash, user_id));');
		} else {
			$db = new PDO('sqlite:db/tags.db');
		}
		return $db;
	}	

	function get_tag_list($url = null){
		global $user;
		$db = get_or_create_db();
		$query = $db->prepare('SELECT * FROM tags WHERE user_id = :uid ORDER BY tag COLLATE NOCASE');
		assert($query->execute([':uid'=>$user->id]),'Was not able to request tag list!');
		return $query->fetchAll(INDEX_FETCH);
	}
	
	function save_tag($url = null,$tags = null,$comment = null, $redirect = true){
		global $user;
		assert($url !== null && $url != '','No value set for url!');
		assert($tags !== null,'No tags set');
		if (!is_array($tags)) $tags = explode(' ',str_replace(',', ' ', $tags));
		
		$url_hash = sha1($url);
		
		$comment_hash = ($comment !== null && $comment != '') ? sha1($comment) : null;
		
		$db = get_or_create_db();
		$query = $db->prepare('INSERT OR IGNORE INTO urls (hash, url) VALUES (:hash, :url);');
		assert($query->execute([':hash'=>$url_hash,':url'=>$url]),'Was not able to store url in database');  		
		
		if ($comment_hash !== null) {
			$query = $db->prepare('INSERT OR IGNORE INTO comments (hash, comment) VALUES (:hash, :comment);');
			assert($query->execute([':hash'=>$comment_hash,':comment'=>$comment]));
			
			$query = $db->prepare('INSERT OR IGNORE INTO url_comments (url_hash, comment_hash, user_id) VALUES (:uhash, :chash, :uid)');
			$query->execute([':uhash'=>$url_hash,':chash'=>$comment_hash,':uid'=>$user->id]); 
		}
		
		$query = $db->prepare('INSERT OR IGNORE INTO tags (tag, url_hash, user_id) VALUES (:tag, :hash, :uid);');
		foreach ($tags as $tag) assert($query->execute([':tag'=>$tag,':hash'=>$url_hash,':uid'=>$user->id]),'Was not able to save tag '.$tag);		
		
		if ($redirect)redirect(getUrl('tag'));
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
	
	function update_tag(&$tag){
		global $user;
		
		$name = trim($_POST['tag']);
		$db = get_or_create_db();
		
		// TODO: die folgenden Fallunterscheidungen lassen sich bestimmt noch aufräumen
		if ($name != '' && strtolower($name) != strtolower($tag->tag)) {
			$tag->tag = $name;
				
			// check, if tag with new name already exists
			$query = $db->prepare('SELECT * FROM tags WHERE user_id = :uid AND tag LIKE :tag');
			assert($query->execute([':uid'=>$user->id,':tag'=>$name]),'Was not able to check, whether tag already exists!');
			$rows = $query->fetchAll(PDO::FETCH_ASSOC);
			if (count($rows)>0){ // tag already exists				
				$existingTag = $rows[0];
				$query = $db->prepare('DELETE FROM tags WHERE id = :tid'); // drop renamed tag
				$query->execute([':tid' => $tag->id]);
				
				$query = $db->prepare('DELETE FROM tags_urls WHERE id = :tid');
				$query->execute([':tid'=>$tag->id]);
				
				$tag->id = $existingTag['id'];
			} else {
				$query = $db->prepare('UPDATE tags SET tag = :tag WHERE id = :tid');
				$query->execute([':tag' => $tag->tag, ':tid' => $tag->id]);

				$query = $db->prepare('DELETE FROM tags_urls WHERE id = :tid');
				$query->execute([':tid'=>$tag->id]);				
			}			
		} else {
			$query = $db->prepare('DELETE FROM tags_urls WHERE id = :tid');
			$query->execute([':tid'=>$tag->id]);				
		}

		$query = $db->prepare('INSERT OR IGNORE INTO tags_urls (id, url) VALUES (:tid, :url)');
		$param=[':tid'=>$tag->id];
		foreach ($_POST['urls'] as $url){
			$url = trim($url);
			if ($url == '') continue;
			$param[':url']=$url;
			$query->execute($param);
		}
		redirect(getUrl('tag',$tag->tag.'/view'));
	}
?>
