<?php

	function get_or_create_db(){
		if (!file_exists('db')){
			assert(mkdir('db'),'Failed to create tag/db directory!');
		}
		assert(is_writable('db'),'Directory tag/db not writable!');
		if (!file_exists('db/tasks.db')){
			$db = new PDO('sqlite:db/tags.db');
			$db->query('CREATE TABLE tags (id INTEGER PRIMARY KEY, tag VARCHAR(255), user_id INT NOT NULL, UNIQUE(user_id, tag));');
			$db->query('CREATE TABLE tags_urls (id INTEGER NOT NULL, url TEXT NOT NULL, PRIMARY KEY (id, url))');
		} else {
			$db = new PDO('sqlite:db/tags.db');
		}
		return $db;
	}	

	function get_tag_list(){
		global $user;
		$db = get_or_create_db();
		$sql = 'SELECT * FROM tags WHERE user_id = :uid ORDER BY tag';
		$args = array(':uid'=>$user->id);
		$query = $db->prepare($sql);
		assert($query->execute($args),'Was not able to request tag list!');
		$results = $query->fetchAll(INDEX_FETCH);
		return $results;
	}
	
	function save_tag($url = null,$tags = null){
		global $user;
		assert($url !== null && $url != '','No value set for url!');
		assert($tags !== null,'No tags set');
		if (!is_array($tags)) $tags = explode(' ',str_replace(',', ' ', $tags));
		
		$tag_ids = [];
		$new_tags = [];
		$db = get_or_create_db();
		$query = $db->prepare('SELECT * FROM tags WHERE user_id = :uid AND tag LIKE :tag');
		$param = [':uid'=>$user->id];
		foreach ($tags as $tag){
			$tag = trim($tag);
			if ($tag == '') continue;
			$param[':tag'] = $tag;
			$query->execute($param);
			$row = $query->fetch(INDEX_FETCH);
			if ($row){
				$tag_ids[] = $row['id'];
			} else $new_tags[] = $tag;		
		}
		$query = $db->prepare('INSERT INTO tags (user_id, tag) VALUES (:uid, :tag)');
		foreach ($new_tags as $tag){
			$param[':tag'] = $tag;
			$query->execute($param);
			$tag_ids[] = $db->lastInsertId();
		}

		$query = $db->prepare('INSERT INTO tags_urls (id, url) VALUES (:tid, :url)');
		$param = [':url'=>$url];
		foreach ($tag_ids as $tid){
			$param[':tid'] = $tid;
			$query->execute($param);
		}
		
		redirect(getUrl('tag'));
	}
	
	function load_tag($tag = null){
		global $user;
		assert($tag !== null,'Called load tag, but no tag given!');
		$db = get_or_create_db();
		
		$query = $db->prepare('SELECT * FROM tags WHERE user_id = :uid AND tag = :tag');
		assert($query->execute([':uid'=>$user->id,':tag'=>$tag]),'Was not able to laod tag form database!');
		$tag = $query->fetch(PDO::FETCH_ASSOC);
		if ($tag){
			$tag = objectFrom($tag);
			$query = $db->prepare('SELECT url FROM tags_urls WHERE id = :tid');
			assert($query->execute([':tid'=>$tag->id]));
			$urls = $query->fetchAll(PDO::FETCH_COLUMN);
			if ($urls) $tag->urls = $urls;
			return objectFrom($tag);
		}
		return null;
	}
	
	function update_tag(&$tag){
		global $user;
		debug($tag);
		debug($_POST);
		$name = trim($_POST['tag']);
		$db = get_or_create_db();
		if ($name != '') {
			$tag->tag = $name;
			$query = $db->prepare('UPDATE tags SET tag = :tag WHERE id = :tid');
			$query->execute([':tag' => $tag->tag, ':tid' => $tag->id]);
		}
		$query = $db->prepare('DELETE FROM tags_urls WHERE id = :tid');
		$query->execute([':tid'=>$tag->id]);

		$query = $db->prepare('INSERT INTO tags_urls (id, url) VALUES (:tid, :url)');
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
