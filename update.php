<?php include 'controller.php';

require_login('poll');

$db = get_or_create_db();

$sql = 'SELECT name FROM sqlite_master WHERE type="table" ORDER BY name';
$query = $db->prepare($sql);

if (!$query->execute()) error("0x000: Was not able to request list of tables.");
if (no_error()) {
	$rows = $query->fetchAll(PDO::FETCH_COLUMN);
	if (!in_array('settings', $rows)) update0();
	$last_db_version = db_version();
	if ($last_db_version === null) updateDB(DB_VERSION); // new installations create a database with the newest sheme, so there is no need to update.

	while (no_error() && $last_db_version < DB_VERSION){ // older installations need to update
		$last_db_version++;
		updateDB($last_db_version);
	}
}
if (no_error()) info("You are up-to-date!");

function update0(){
	global $db;

	if (no_error()){
		$sql = 'CREATE TABLE settings ( ';
		foreach (Settings::table() as $field => $props) $sql .= field_description($field,$props);
		$sql = str_replace([' ,',', )'],[',',')'],$sql.')');
		$query = $db->prepare($sql);
		if ($query->execute()){
			info("Created settings table.");
		} else error("0x001: Was not able to create settings table: ◊",$sql);
	}

	if (no_error()){
		$query = $db->prepare('REPLACE INTO settings (key, value) VALUES ("db_version", 0)');
		if ($query->execute()) {
			info("Set db_version 0 in settings table");
		} else error("0x006: Was not able insert db version into settings table!");
	}
}

function update1(){
	global $db;

	if (no_error()){
		$sql = 'CREATE TABLE shares (';
		foreach (Poll::shares_table() as $field => $props) $sql .= field_description($field, $props);
		$sql = str_replace([' ,',', )'],[',',')'],$sql.')');
		$query = $db->prepare($sql);
		if ($query->execute()){
			info("Created shares table.");
		} else error("0x002: Was not able to create shares table: ◊",$sql);
	}
}

function updateDB($version){
	global $db;
	info('Attempting to update to db verison ◊.',$version);

	switch ($version){
		case 1: update1(); break;
	}

	if (no_error()){
		$query = $db->prepare('REPLACE INTO settings (key, value) VALUES ("db_version", ?)');
		if ($query->execute([$version])) {
			info("Set db_version ◊ in settings table",$version);
		} else error("0x006: Was not able insert db version into settings table!");
	}

	if (no_error()) info('Update performed');
}

include '../common_templates/head.php';
include '../common_templates/messages.php';

include '../common_templates/closure.php';

