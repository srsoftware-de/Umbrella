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
							head TEXT,
							footer TEXT);');
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
	$query = $db->prepare('UPDATE invoices SET sender = :sender, tax_num = :tax, customer = :cust, customer_num = :cnum, invoice_date = :idate, delivery_date = :ddate, head = :head, footer = :foot WHERE id = :id');	
	assert($query->execute(array(':sender'=>$invoice['sender'],
								 ':tax'=>$invoice['tax_num'],
								 ':cust'=>$invoice['customer'],
								 ':cnum'=>$invoice['customer_num'],
								 ':idate'=>$invoice_date,
								 ':ddate'=>$delivery_date,
								 ':head'=>$invoice['head'],
								 ':foot'=>$invoice['footer'],
								 ':id'=>$id)),'Was not able to update invoice!');
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

function create_invoice($sender = null, $tax_num = null, $customer_contact_id = null, $customer_number = null, $invoice_date = null, $delivery_date = null, $head = null, $footer = null){
	global $user;
	
	assert($sender !== null && trim($sender) != '','Invalid sender passed to create_invoice!');
	assert($tax_num !== null && trim($tax_num) != '','Invalid tax number passed to create_invoice!');
	assert(is_numeric($customer_contact_id),'Invalid customer contact id supplied to create_invoice!');
	
	$contacts = request('contact', 'json_list?id='.$customer_contact_id);
	$vcard = $contacts[$customer_contact_id];
	if ($customer_number === null) $customer_number=$vcard['X-CUSTOMER-NUMBER'];
	$customer = vcard_address($vcard);
	
	$date = time();
	
	$db = get_or_create_db();
	$query = $db->prepare('INSERT INTO invoices (user_id, sender, tax_num, customer, customer_num, invoice_date) VALUES (:uid, :sender, :tax, :cust, :cnum, :date)');
	assert($query->execute(array(':uid'=>$user->id,':sender'=>$sender,':tax'=>$tax_num,':cust'=>$customer,':cnum'=>$customer_number,':date'=>$date)),'Was not able to create invoice!');
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

?>