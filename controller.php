<?php

function get_or_create_db(){
	if (!file_exists('db')) assert(mkdir('db'),'Failed to create invoice/db directory!');
	assert(is_writable('db'),'Directory invoice/db not writable!');
	if (!file_exists('db/invoices.db')){
		$db = new PDO('sqlite:db/invoices.db');
		
		foreach (Invoice::tables() as $table => $fields){		
			$sql = 'CREATE TABLE '.$table.' ( ';
			foreach ($fields as $field => $props){
				$sql .= $field . ' ';
				if (is_array($props)){
					foreach ($props as $prop_k => $prop_v){
						switch (true){
							case $prop_k==='VARCHAR':
								$sql.= 'VARCHAR('.$prop_v.') '; break;
							case $prop_k==='DEFAULT':
								$sql.= 'DEFAULT '.($prop_v === null)?'NULL ':('"'.$prop_v.'" '); break;
							case $prop_k==='KEY':
								assert($prop_v === 'PRIMARY','Non-primary keys not implemented in invoice/controller.php!');
								$sql.= 'PRIMARY KEY '; break;
							default:
								$sql .= $prop_v.' ';
						}	
					}		
					$sql .= ", ";
				} else $sql .= $props.", ";
			}
			$sql = str_replace([' ,',', )'],[',',')'],$sql.')');
			debug($sql);
			$query = $db->prepare($sql);
			assert($db->query($sql),'Was not able to create companies table in companies.db!');
		}
	} else {
		$db = new PDO('sqlite:db/companies.db');
	}
	return $db;

}

class Invoice {
	function __construct($name = null){
		assert($name !== null,'Invoice name must not be empty');
		$this->name = $name;
	}

	static function tables(){
		$company_settings = [
			'company_id' 			=> ['INTEGER','KEY'=>'PRIMARY'],
			'default_invoice_header' 	=> 'TEXT',
			'default_invoice_footer'	=> 'TEXT',
			'invoice_prefix'		=> ['TEXT','DEFAULT'=>'R'],
			'invoice_suffix'		=> ['TEXT','DEFAULT'=>null],
			'invoice_number'		=> ['INT','NOT NULL'],
		];

		$invoices = [
			'id'			=> ['INTEGER','KEY'=>'PRIMARY'],
			'invoice_date'		=> ['TIMESTAMP','NOT NULL'],
			'invoice_number'	=> ['TEXT','NOT NULL'],
			'delivery_date'		=> 'TIMESTAMP',
			'head'			=> 'TEXT',
			'footer'		=> 'TEXT',
			'company_id'		=> ['INT','NOT NULL'],
			'currency'		=> ['VARCHAR'=>10,'NOT NULL'],
			'sender'		=> ['TEXT','NOT NULL'],
			'tax_number'		=> ['VARCHAR'=>255],
			'bank_account'		=> 'TEXT',
			'court'			=> 'TEXT',
			'customer'		=> 'TEXT',
			'customer_number'	=> 'INT',
		];

		$invoice_positions = [
			'invoice_id'	=> ['INTEGER','NOT NULL'],
			'pos'		=> ['INTEGER','NOT NULL'],
			'item_code'	=> ['VARCHAR'=>50],
			'amount'	=> ['INTEGER','NOT NULL','DEFAULT'=>1],
			'unit'		=> ['VARCHAR'=> 12],
			'title'		=> ['VARCHAR'=>255],
			'description'	=> 'TEXT',
			'single_price'	=> 'INTEGER',
			'tax'		=> 'INTEGER',
		];

		return compact(['company_settings', 'invoices', 'invoice_positions']);
	}
	
	function patch($data = array()){
		if (!isset($this->dirty)) $this->dirty = [];
		foreach ($data as $key => $val){
			if ($key === 'id' && isset($this->id)) continue;
			if (isset($this->{$key}) && $this->{$key} != $val) $this->dirty[] = $key;
			$this->{$key} = $val;
		}
	}
	
	static function load($ids = null){
		global $user;
		$db = get_or_create_db();
	
		// TODO: implement
		return null;
	}
}

?>
