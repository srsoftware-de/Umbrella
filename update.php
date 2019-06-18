<?php include 'controller.php';

require_login('document');

$db = get_or_create_db();

$sql = 'SELECT name FROM sqlite_master WHERE type="table" ORDER BY name';
$query = $db->prepare($sql);

if (!$query->execute()) error("0x000: Was not able to request list of tables.");
if (no_error()) {
	$rows = $query->fetchAll(PDO::FETCH_COLUMN);
	if (!in_array('settings', $rows)) update0();
	if (empty(db_version())) updateDB(DB_VERSION); // new installations create a database with the newest sheme, so there is no need to update.
	$last_db_version = db_version();
	while ($last_db_version < DB_VERSION){ // oder installations need to update
		$last_db_version++;
		error('No update defined for db version '.$last_db_version);
		break;
		updateDB($last_db_version);
	}
}
if (no_error()) info("You are up-to-date!");

function update0(){
	global $db;

	if (no_error()){
		$sql = 'CREATE TABLE diagrams ( ';
		foreach (Diagram::fields() as $field => $props) $sql .= field_description($field,$props);
		$sql = str_replace([' ,',', )'],[',',')'],$sql.')');
		$query = $db->prepare($sql);
		if ($query->execute()){
			info("Created diagrams table.");
		} else error("0x001: Was not able to create diagrams table: ?",$sql);
	}

	if (no_error()){
		$sql = 'CREATE TABLE parties ( ';
		foreach (Party::fields() as $field => $props) $sql .= field_description($field,$props);
		$sql = str_replace([' ,',', )'],[',',')'],$sql.')');
		$query = $db->prepare($sql);
		if ($query->execute()){
			info("Created parties table.");
		} else error("0x001: Was not able to create parties table: ?",$sql);
	}

	if (no_error()){
		$sql = 'CREATE TABLE phases ( ';
		foreach (Phase::fields() as $field => $props) $sql .= field_description($field,$props);
		$sql = str_replace([' ,',', )'],[',',')'],$sql.')');
		$query = $db->prepare($sql);
		if ($query->execute()){
			info("Created phases table.");
		} else error("0x001: Was not able to create phases table: ?",$sql);
	}

	if (no_error()){
		$sql = 'CREATE TABLE steps ( ';
		foreach (Step::fields() as $field => $props) $sql .= field_description($field,$props);
		$sql = str_replace([' ,',', )'],[',',')'],$sql.')');
		$query = $db->prepare($sql);
		if ($query->execute()){
			info("Created steps table.");
		} else error("0x001: Was not able to create steps table: ?",$sql);
	}

	if (no_error()){
		$sql = 'CREATE TABLE settings ( ';
		foreach (Settings::table() as $field => $props) $sql .= field_description($field,$props);
		$sql = str_replace([' ,',', )'],[',',')'],$sql.')');
		$query = $db->prepare($sql);
		if ($query->execute()){
			info("Created settings table.");
		} else error("0x007: Was not able to create settings table: ?",$sql);
	}
}

function updateDB($version){
	global $db;
	if (no_error()){
		$query = $db->prepare('REPLACE INTO settings (key, value) VALUES ("db_version", ?)');
		if ($query->execute([$version])) {
			info("Set db_version in settings table");
		} else error("0x008: Was not able insert db version into settings table!");
	}

	if (no_error()) info('Update performed');
}

include '../common_templates/head.php';
include '../common_templates/messages.php';

include '../common_templates/closure.php';

