<?php include_once 'model.php';

	function hour($date){
		if ($date === null||$date == '') return null;
		return strtotime($date) / 3600;
	}
	
	function update($db){
	    
	    $sql = 'SELECT name FROM sqlite_master WHERE type="table" ORDER BY name';
	    $query = $db->prepare($sql);
	    
	    if (!$query->execute()) error("0x000: Was not able to request list of tables.");
	    if (no_error()) {
	        $rows = $query->fetchAll(PDO::FETCH_COLUMN);
	        if (!in_array('settings', $rows)) update0($db);
	        if (empty(Settings::db_version())) updateDB($db,DB_VERSION); // new installations create a database with the newest sheme, so there is no need to update.
	        $last_db_version = Settings::db_version();
	        while ($last_db_version < DB_VERSION){ // older installations need to update
	            $last_db_version++;
	            error('No update defined for db version '.$last_db_version);
	            break;
	            updateDB($db,$last_db_version);
	        }
	    }
	    if (no_error()) info("You are up-to-date!");
	}
	
	function update0($db){
	    if (no_error()){
	        $sql = 'ALTER TABLE projects ADD COLUMN show_closed BOOLEAN DEFAULT FALSE;';
	        $query = $db->prepare($sql);
	        if ($query->execute()){
	            info("Extended 'projects' table.");
	        } else error("0x001: Was not able to add column 'show_closed' to projects table");
	    }
	
	    if (no_error()){
	        $sql = 'CREATE TABLE settings ( ';
	        foreach (Settings::table() as $field => $props) $sql .= field_description($field,$props);
	        $sql = str_replace([' ,',', )'],[',',')'],$sql.')');
	        error_log($sql);
	        $query = $db->prepare($sql);
	        if ($query->execute()){
	            info("Created settings table.");
	        } else error("0x002: Was not able to create settings table: ?",$sql);
	    }
	}
	
	function updateDB($db, $version){
	    if (no_error()){
	        $query = $db->prepare('REPLACE INTO settings (key, value) VALUES ("db_version", ?)');
	        if ($query->execute([$version])) {
	            info("Set db_version in settings table");
	        } else error("0x003: Was not able insert db version into settings table!");
	    }
	    
	    if (no_error()) info('Update performed');
	}
?>
