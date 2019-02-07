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
		$sql = 'CREATE TABLE company_customer_settings ( ';
		foreach (CompanyCustomerSettings::table() as $field => $props) $sql .= field_description($field,$props);
		$sql = str_replace([' ,',', )'],[',',')'],$sql.')');
		$query = $db->prepare($sql);
		if ($query->execute()){
			info("Created company_customer_settings table.");
		} else error("0x001: Was not able to create company_customer_settings table: ?",$sql);
	}

	if (no_error()){
		$sql = 'CREATE TABLE company_settings_new ( ';
		foreach (CompanySettings::table() as $field => $props) $sql .= field_description($field,$props);
		$sql = str_replace([' ,',', )'],[',',')'],$sql.')');
		$query = $db->prepare($sql);
		if ($query->execute()){
			info("Created new company_settings table.");
		} else error("0x002: Was not able to create new company_settings table: ?",$sql);
	}

	if (no_error()){
		$sql = 'SELECT company_id, document_type_id, type_prefix, type_suffix, type_number FROM company_settings';
		$query = $db->prepare($sql);
		if (!$query->execute()) error("0x003: Was not able to read old company settings table!");
		if (no_error()){
			$rows = $query->fetchAll(PDO::FETCH_NUM);
			$sql = 'INSERT INTO company_settings_new (company_id, document_type_id, type_prefix, type_suffix, type_number) VALUES (?, ?, ?, ?, ?)';
			$query = $db->prepare($sql);
			foreach ($rows as $row){
				if ($query->execute($row)) {
					info('Transferred settings to new company_settings table.');
				} else {
					error("0x004: Was not able to transfer settings from company_settings to new company_settings!");
					break;
				}
			}
		}
	}

	if (no_error()){
		$query = $db->prepare('DROP TABLE company_settings');
		if ($query->execute()){
			info('Dropped old company_settings table');
		} else error("0x005: Was not able to drop old company_settings table");
	}

	if (no_error()){
		$query = $db->prepare('ALTER TABLE company_settings_new RENAME TO company_settings');
		if ($query->execute()){
			info('Moved new company_settings table in place.');
		} else error("0x006: Was not able to rename new company_settings table");
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
			info("Introduced db_version in settings table");
		} else error("0x008: Was not able insert db version into settings table!");
	}

	if (no_error()) info('Update performed');
}

include '../common_templates/head.php';
include '../common_templates/messages.php';

include '../common_templates/closure.php';

