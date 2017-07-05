<?php 

if (!isset($services['files'])) die('Contact service requres file service to be active!');
	
function get_or_create_db(){
	if (!file_exists('db')){
		assert(mkdir('db'),'Failed to create time/db directory!');
	}
	assert(is_writable('db'),'Directory invoice/db not writable!');
	if (!file_exists('db/times.db')){
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
							head TEXT,
							footer TEXT);');
		$db->query('CREATE TABLE invoice_positions(
						invoice_id INT NOT NULL,
						pos INT NOT NULL,
						amount DOUBLE NOT NULL DEFAULT 1,
						unit VARCHAR(30),
						short VARCHAR(30),
						title TEXT NOT NULL,
						explanation TEXT,
						single_price DOUBLE NOT NULL DEFAULT 0,
						tax DOUBLE,
						PRIMARY KEY(invoice_id, pos));');
	} else {
		$db = new PDO('sqlite:db/invoices.db');
	}
	return $db;
}

function vcard_address($vcard){
	$adr = '';
	if (isset($vcard['N'])){
		$names = explode(';',$vcard['N']);
		$adr .= $names[2].' '.$names[1]."\n";
	}
	if (isset($vcard['ORG'])){
		$org = str_replace(';', ', ', $vcard['ORG']);
		$adr .= $org."\n";
	}
	if (isset($vcard['ADR'])){
		$parts = explode(';', $vcard['ADR']);
		$adr .= $parts[3]."\n".$parts[6].' '.$parts[4]."\n".$parts[5].' '.$parts[7];
	}
	return $adr;
}


function list_invoices(){
		global $user;
		$db = get_or_create_db();
		$query = $db->prepare('SELECT * FROM invoices WHERE user_id = :uid');
		assert($query->execute(array(':uid'=>$user->id)),'was not able to fetch invoices for you!');
		return $query->fetchAll(INDEX_FETCH);
}

function create_invoice($sender = null, $tax_num = null, $customer_contact_id = null, $customer_number = null, $invoice_date = null, $delivery_date = null, $head = null, $footer = null){
	global $user;
	
	assert($sender !== null && trim($sender) != '','Invalid sender passed to create_invoice!');
	assert($tax_num !== null && trim($tax_num) != '','Invalid tax number passed to create_invoice!');
	assert(is_numeric($customer_contact_id),'Invalid customer contact id supplied to create_invoice!');
	
	$contacts = request('contact', 'json_list?id='.$customer_contact_id);
	$vcard = $contacts[$customer_contact_id];
	$customer = vcard_address($vcard);
	
	$date = time();
	
	$db = get_or_create_db();
	$query = $db->prepare('INSERT INTO invoices (user_id, sender, tax_num, customer, invoice_date) VALUES (:uid, :sender, :tax, :cust, :date)');
	assert($query->execute(array(':uid'=>$user->id,':sender'=>$sender,':tax'=>$tax_num,':cust'=>$customer,':date'=>$date)),'Was not able to create invoice!');
	return $db->lastInsertId();
}
?>