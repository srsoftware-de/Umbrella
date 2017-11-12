<?php 

if (!isset($services['files'])) die('Contact service requres file service to be active!');
	
function get_or_create_db(){
	if (!file_exists('db')){
		assert(mkdir('db'),'Failed to create time/db directory!');
	}
	assert(is_writable('db'),'Directory invoice/db not writable!');
	if (!file_exists('db/invoices.db')){
		$db = new PDO('sqlite:db/invoices.db');
		$db->query('CREATE TABLE invoices (
							id INTEGER PRIMARY KEY,
							user_id INTEGER NOT NULL,
							sender TEXT,
							tax_num TEXT,
							customer TEXT,
							customer_num TEXT,
							invoice_date DATE NOT NULL,
							delivery_date DATE,
							bank_account TEXT,
							head TEXT,
							footer TEXT,
							court TEXT);');
		$db->query('CREATE TABLE invoice_positions(
						invoice_id INT NOT NULL,
						pos INT NOT NULL,
						item_code VARCHAR(30) NOT NULL,
						amount DOUBLE NOT NULL DEFAULT 1,
						unit VARCHAR(30),
						title TEXT NOT NULL,
						description TEXT,
						single_price DOUBLE NOT NULL DEFAULT 0,
						tax DOUBLE,
						PRIMARY KEY(invoice_id, pos));');
		$db->query('CREATE TABLE settings(
						user_id INTEGER PRIMARY KEY,
						decimal_separator CHAR,
						thousands_separator CHAR,
						currency VARCHAR(30),
						decimals INT,
						default_invoice_header TEXT,
						default_invoice_footer TEXT,
						invoice_prefix TEXT,
						invoice_suffix TEXT,
						invoice_number INT);');
	} else {
		$db = new PDO('sqlite:db/invoices.db');
	}
	return $db;
}

function add_invoice_position($invoice_id,$code,$title,$description,$amount,$unit,$price,$tax){
	$db = get_or_create_db();

	$query = $db->prepare('SELECT MAX(pos) FROM invoice_positions WHERE invoice_id = :id');
	assert($query->execute(array(':id'=>$invoice_id)),'Was not able to get last invoice position!');
	$row = $query->fetch(PDO::FETCH_COLUMN);
	$pos = ($row === null)?1:$row+1;
	
	$query = $db->prepare('INSERT INTO invoice_positions (invoice_id, pos, item_code, amount, unit, title, description, single_price, tax) VALUES (:id, :pos, :code, :amt, :unit, :ttl, :desc, :price, :tax)');
	$args = array(':id'=>$invoice_id,':pos'=>$pos,':code'=>$code,':amt'=>$amount,':unit'=>$unit,':ttl'=>$title,':desc'=>$description,':price'=>$price,':tax'=>$tax);
	assert($query->execute($args),'Was not able to store new postion for invoice '.$invoice_id.'!');
}

function save_invoice_position($position){
	$db = get_or_create_db();
	
	$invoice_id = $position['invoice_id'];
	$pos = $position['pos'];
	
	$args = array();
	$keys = array_keys($position);	
	$sql = 'UPDATE invoice_positions SET ';
	foreach ($keys as $key){
		$args[':'.$key] = $position[$key];
		if ($key == 'invoice_id' || $key == 'pos') continue;
		$sql .= $key.' = :'.$key.', ';
	}
	$sql = substr($sql, 0,-2).' WHERE invoice_id = :invoice_id AND pos = :pos';
	$query = $db->prepare($sql);
	assert($query->execute($args),'Was not able to update invoice position '.$pos.' of invoice '.$invoice_id.'!');
}

function save_invoice($id = null, $invoice = null){
	assert(is_numeric($id),'No valid invoice id passed to save_invoice!');
	assert(is_array($invoice),'No invoice passed to save_invoice');

	$invoice_date = strtotime($invoice['invoice_date']);	
	$delivery_date = strtotime($invoice['delivery_date']);
	
	$db = get_or_create_db();
	$query = $db->prepare('UPDATE invoices SET sender = :sender, tax_num = :tax, bank_account = :bank, customer = :cust, customer_num = :cnum, invoice_date = :idate, delivery_date = :ddate, head = :head, footer = :foot WHERE id = :id');	
	assert($query->execute(array(':sender'=>$invoice['sender'],
								 ':tax'=>$invoice['tax_num'],
								 ':bank'=>$invoice['bank_account'],
								 ':cust'=>$invoice['customer'],
								 ':cnum'=>$invoice['customer_num'],
								 ':idate'=>$invoice_date,
								 ':ddate'=>$delivery_date,
								 ':head'=>$invoice['head'],
								 ':foot'=>$invoice['footer'],
								 ':id'=>$id)),'Was not able to update invoice!');
}

function conclude_vcard($vcard){
	$short = '';
	if (isset($vcard['FN'])) return $vcard['FN'];
	if (isset($vcard['N'])){
		$names = explode(';',$vcard['N']);
		return $names[1].' '.$names[0];
	}
	debug('error in conclude_vcard',1);
}

function vcard_address($vcard){
	$result = '';
	if (isset($vcard['N'])){
		$names = explode(';',$vcard['N']);
		$result .= $names[1].' '.$names[0]."\n";
	}
	if (isset($vcard['ORG'])){
		$org = str_replace(';', ', ', $vcard['ORG']);
		$result .= $org."\n";
	}
	if (isset($vcard['ADR'])){
		$adr=$vcard['ADR'];
		if (is_array($adr)){
			if (isset($adr['TYPE=WORK'])) {
				$adr = $adr['TYPE=WORK'];
			} else $adr=reset($adr);
		}	
		$adr=explode(';', $adr);
		$result .= $adr[2]."\n".$adr[5].' '.$adr[3]."\n".$adr[4].' '.$adr[6];
	}
	return $result;
}


function load_invoices($ids = array()){
	$reset = is_numeric($ids); // if we get only one id, we will return a single element instad of an array
	if ($reset) $ids = array($ids);
	assert(is_array($ids),'Invalid invoice id passed to load_tasks!');
	$sql = 'SELECT id,* FROM invoices';
	if (!empty($ids)){
		$qMarks = str_repeat('?,', count($ids) - 1) . '?';
		$sql .=  ' WHERE id IN ('.$qMarks.')';
	}
	$db = get_or_create_db();
	$query = $db->prepare($sql);
	assert($query->execute($ids),'Was not able to load invoice!');
	$invoices = $query->fetchAll(INDEX_FETCH);
	if ($reset) return reset($invoices);
	return $invoices;
}

function create_invoice($sender = null, $tax_num = null, $bank_account = null, $court = null, $customer_contact_id = null, $customer_number = null, $invoice_date = null, $delivery_date = null, $head = null, $footer = null){
	global $user;
	assert($sender !== null && trim($sender) != '','Invalid sender passed to create_invoice!');
	assert($tax_num !== null && trim($tax_num) != '','Invalid tax number passed to create_invoice!');
	assert(is_numeric($customer_contact_id),'Invalid customer contact id supplied to create_invoice!');
	$contacts = request('contact', 'json_list',['id'=>$customer_contact_id]);
	$vcard = $contacts[$customer_contact_id];
	if ($customer_number === null) $customer_number=$vcard['X-CUSTOMER-NUMBER'];
	$customer = vcard_address($vcard);
	
	$date = time();
	
	$db = get_or_create_db();
	$query = $db->prepare('INSERT INTO invoices (user_id, sender, tax_num, bank_account, customer, customer_num, invoice_date, court) VALUES (:uid, :sender, :tax, :bank, :cust, :cnum, :date, :court)');
	assert($query->execute(array(':uid'=>$user->id,':sender'=>$sender,':tax'=>$tax_num,':bank'=>$bank_account,':cust'=>$customer,':cnum'=>$customer_number,':date'=>$date,':court'=>$court)),'Was not able to create invoice!');
	return $db->lastInsertId();
}

function update_invoice($invoice){
	if (!isset($_POST['customer'])) return;
	$fields = array('customer');
	$changed = array();	
	foreach ($fields as $field){
		if ($invoice[$field] != $_POST[$field]){
			debug($_POST,1);
			
			$invoice[$field] = $_POST[$field];
			$changed[] = $field;
		}
	}
	if (!empty($changed)){
		debug($invoice);
		debug($changed,1);
	}
}

function load_positions(&$invoice){
	$db = get_or_create_db();
	$query = $db->prepare('SELECT pos,* FROM invoice_positions WHERE invoice_id = :id');
	assert($query->execute(array(':id'=>$invoice['id'])),'Was not able to load invoice positions!');
	$invoice['positions'] = $query->fetchAll(INDEX_FETCH);	
}

function elevate($invoice_id = null, $position = null){
	assert(is_numeric($invoice_id),'No valid invoice id passed to elevate!');
	assert(is_numeric($position),'No valid invoice id passed to elevate!');
	$pos_minus_1 = $position -1;
	$db = get_or_create_db();
	$query = $db->prepare('UPDATE invoice_positions SET pos = -1 WHERE pos = :pm1 AND invoice_id = :id');
	assert($query->execute(array(':pm1'=>$pos_minus_1,':id'=>$invoice_id)),'Was not able to alter pos field of invoice position '.$pos_minus_1);
	
	$query = $db->prepare('UPDATE invoice_positions SET pos = :pm1 WHERE pos = :pos AND invoice_id = :id');
	assert($query->execute(array(':pos'=>$position,':pm1'=>$pos_minus_1,':id'=>$invoice_id)),'Was not able to alter pos field of invoice position '.$position);
		
	$query = $db->prepare('UPDATE invoice_positions SET pos = :pos WHERE pos = -1 AND invoice_id = :id');
	assert($query->execute(array(':pos'=>$position,':id'=>$invoice_id)),'Was not able to alter pos field of invoice position '.$position);	
}

function get_settings(){
	global $user;
	$db = get_or_create_db();
	$query = $db->prepare('SELECT * FROM settings WHERE user_id = :uid');
	assert($query->execute([':uid'=>$user->id]),t('Was no able to load user settings for invoices'));
	return $query->fetch(INDEX_FETCH);
}

function save_settings($settings){
	global $user;
	$db = get_or_create_db();
	$args = array(':uid'=>$user->id);
	foreach ($settings as $key => $value){
		$args[':'.$key] = $value;
	}
	$query = $db->prepare('INSERT OR REPLACE INTO settings (user_id, 
											decimal_separator,
											thousands_separator,
											currency,
											decimals,
											default_invoice_header,
											default_invoice_footer,
											invoice_prefix, 
											invoice_suffix, 
											invoice_number)
							VALUES 	(:uid,
									:decimal_separator,
									:thousands_separator,
									:currency, :decimals,
									:default_invoice_header,
									:default_invoice_footer,
									:invoice_prefix,
									:invoice_suffix,
									:invoice_number)');
	assert($query->execute($args),t('Was not able to store your invoice settings'));
}

?>