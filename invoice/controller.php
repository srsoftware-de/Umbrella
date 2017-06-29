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

function list_invoices($user_id = null){
		assert(is_numeric($user_id),'No valid user id passed to list_invoices!');
		$db = get_or_create_db();
}

function create_invoice($sender = null, $tax_num = null, $customer = null, $customer_number = null, $invoice_date = null, $delivery_date = null, $head = null, $footer = null){	
}
?>