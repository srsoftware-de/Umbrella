<?php include 'controller.php';

$user = User::require_login();

$db = get_or_create_db();

$sql = 'SELECT name FROM sqlite_master WHERE type="table" ORDER BY name';
$query = $db->prepare($sql);

if (!$query->execute()) error("0x000: Was not able to request list of tables.");
if (no_error()) {
	$rows = $query->fetchAll(PDO::FETCH_COLUMN);
	if (!in_array('settings', $rows)) update0();
	$last_db_version = db_version();
	if ($last_db_version === null) updateDB(DB_VERSION); // new installations create a database with the newest sheme, so there is no need to update.

	while (no_error() && $last_db_version < DB_VERSION){ // oder installations need to update
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
		$sql = 'CREATE TABLE messages (';
		foreach (Message::table() as $field => $props) $sql .= field_description($field, $props);
		$sql = str_replace([' ,',', )'],[',',')'],$sql.')');
		$query = $db->prepare($sql);
		if ($query->execute()){
			info("Created messages table.");
		} else error("0x002: Was not able to create messages table: ◊",$sql);
	}

	if (no_error()){
		$sql = 'CREATE TABLE recipients (';
		foreach (Message::recipients() as $field => $props) $sql .= field_description($field, $props);
		$sql = str_replace([' ,',', )'],[',',')'],$sql.')');
		$query = $db->prepare($sql);
		if ($query->execute()){
			info("Created recipients table.");
		} else error("0x003: Was not able to create recipients table: ◊",$sql);
	}
}

function update2(){
	global $db;

	if (no_error()){
		$sql = 'ALTER TABLE users ADD COLUMN message_delivery VARCHAR(100) DEFAULT "'.Message::DELIVER_INSTANTLY.'"';
		$query = $db->prepare($sql);
		if ($query->execute()){
			info('Added message_delivery column.');
		} else error("0x004: Was not able to add message_delivery column: ◊",$sql);
	}

	if (no_error()){
		$sql = 'ALTER TABLE users ADD COLUMN last_logoff INT DEFAULT NULL';

		$query = $db->prepare($sql);
		if ($query->execute()){
			info('Added last_logoff column.');
		} else error("0x005: Was not able to add last_logoff column: ◊",$sql);
	}
}

function update3(){
	global $db;

	if (no_error()){
		$sql = 'CREATE TABLE foreign_services (';
		foreach (ForeignService::table() as $field => $props) $sql .= field_description($field, $props);
		$sql = str_replace([' ,',', )'],[',',')'],$sql.')');
		$query = $db->prepare($sql);
		if ($query->execute()){
			info("Created foreign services table.");
		} else error("0x006: Was not able to create foreign services table: ◊",$sql);
	}

	if (no_error()){
		$sql = 'CREATE TABLE foreign_logins (';
		foreach (ForeignService::login_table() as $field => $props) $sql .= field_description($field, $props);
		$sql = str_replace([' ,',', )'],[',',')'],$sql.')');
		$query = $db->prepare($sql);
		if ($query->execute()){
			info("Created foreign logins table.");
		} else error("0x007: Was not able to create foreign logins table: ◊",$sql);
	}
}

function updateDB($version){
	global $db;
	info('Attempting to update to db verison ◊.',$version);

	switch ($version){
		case 1: update1(); break;
		case 2: update2(); break;
		case 3: update3(); break;
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

