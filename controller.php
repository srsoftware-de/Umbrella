<?php

class InvoicePosition{
	static function table(){	
		return [
			'invoice_id'	=> ['INTEGER','NOT NULL'],
			'pos'		=> ['INTEGER','NOT NULL'],
			'item_code'	=> ['VARCHAR'=>50],
			'amount'	=> ['INTEGER','NOT NULL','DEFAULT'=>1],
			'unit'		=> ['VARCHAR'=> 12],
			'title'		=> ['VARCHAR'=>255],
			'description'	=> 'TEXT',
			'single_price'	=> 'INTEGER',
			'tax'		=> 'INTEGER',
			'time_id'	=> 'INTEGER',
		];
	}

	function __construct(Invoice $invoice){
		$db = get_or_create_db();

		$query = $db->prepare('SELECT max(pos) AS pos FROM invoice_positions WHERE invoice_id = :iid');
		assert($query->execute([':iid'=>$invoice->id]),'Was not able to read invoice position table');
		$this->pos = reset($query->fetch(PDO::FETCH_ASSOC)) +1;
		$this->invoice_id = $invoice->id;
	}
	
	public function copy(Invoice $invoice){
		$new_position = new InvoicePosition($invoice);
		foreach ($this as $field => $value){
			if (in_array($field, ['id','invoice_id'])) continue;
			$new_position->{$field} = $value;
		}
		return $new_position->save();
	}
	
	public function patch($data = array(),$set_dirty = true){
		if (!isset($this->dirty)) $this->dirty = [];
		foreach ($data as $key => $val){
			if (!isset($this->{$key}) || $this->{$key} != $val) $this->dirty[] = $key;
			$this->{$key} = $val;
		}
		return $this;
	}
	
	public function save(){
		global $services;
		$db = get_or_create_db();
		$query = $db->prepare('SELECT count(*) AS count FROM invoice_positions WHERE invoice_id = :iid AND pos = :pos');
		assert($query->execute([':iid'=>$this->invoice_id,':pos'=>$this->pos]),'Was not able to read from invoice positions table!');
		$count = reset($query->fetch(PDO::FETCH_ASSOC));
		if ($count == 0){ // new!
			$known_fields = array_keys(InvoicePosition::table());
			$fields = [];
			$args = [];
			foreach ($known_fields as $f){
				if (isset($this->{$f})){
					$fields[]=$f;
					$args[':'.$f] = $this->{$f};
				}
			}
			$sql = 'INSERT INTO invoice_positions ( '.implode(', ',$fields).' ) VALUES ( :'.implode(', :',$fields).' )';
			$query = $db->prepare($sql);
			assert($query->execute($args),'Was not able to insert new row into invoice_positions');
		} else {
			if (!empty($this->dirty)){
				$sql = 'UPDATE invoice_positions SET';
				$args = [':iid'=>$this->invoice_id,':pos'=>$this->pos];
				foreach ($this->dirty as $field){
					$sql .= ' '.$field.'=:'.$field.',';
					$args[':'.$field] = $this->{$field};
				}
				$this->dirty = [];
				$sql = rtrim($sql,',').' WHERE invoice_id = :iid AND pos = :pos';
				$query = $db->prepare($sql);
				assert($query->execute($args),'Was no able to update invoice_positions in database!');
			}
		}
		
		return $this;
	}
	
	static function load($invoice){
		$db = get_or_create_db();
		$sql = 'SELECT pos,* FROM invoice_positions WHERE invoice_id = :iid ORDER BY pos';
		$args = [':iid'=>$invoice->id];
		$query = $db->prepare($sql);
		assert($query->execute($args),'Was not able to load invoie positions.');
		$rows = $query->fetchAll(INDEX_FETCH);
		$result = [];
		foreach ($rows as $pos => $row){
			$invoicePosition = new InvoicePosition($invoice);
			$invoicePosition->patch($row);
			$invoicePosition->dirty = [];
			$result[$pos] = $invoicePosition;
		}
		return $result;
	}
	
	public function delete(){
		global $services;
		$db = get_or_create_db();
		$query = $db->prepare('DELETE FROM invoice_positions WHERE invoice_id = :iid AND pos = :pos');
		assert($query->execute([':iid'=>$this->invoice_id,':pos'=>$this->pos]),'Was not able to remove entry from invoice positions table!');
		
		$query = $db->prepare('UPDATE invoice_positions SET pos = pos-1 WHERE invoice_id = :iid AND pos > :pos');
		assert($query->execute([':iid'=>$this->invoice_id,':pos'=>$this->pos]));
		if (isset($this->time_id) && $this->time_id !== null && isset($services['time'])){
			request('time','update_state',['OPEN'=>$this->time_id]);
		}

		return $this;
	}
	
}

function get_or_create_db(){
	if (!file_exists('db')) assert(mkdir('db'),'Failed to create invoice/db directory!');
	assert(is_writable('db'),'Directory invoice/db not writable!');
	if (!file_exists('db/invoices.db')){
		$db = new PDO('sqlite:db/invoices.db');
		
		$tables = [
			'invoices'=>Invoice::table(),
			'invoice_positions'=>InvoicePosition::table(),
			'company_settings'=>CompanySettings::table(),
			'templates'=>Template::table(),
		];
		
		foreach ($tables as $table => $fields){		
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
			$query = $db->prepare($sql);
			assert($db->query($sql),'Was not able to create companies table in companies.db!');
		}
	} else {
		$db = new PDO('sqlite:db/invoices.db');
	}
	return $db;

}

class CompanySettings{
	function __construct($company_id){
		$this->company_id = $company_id;
		
		$this->offer_prefix = 'A';
		$this->offer_suffix = '';
		$this->offer_number = 1;
		$this->default_offer_header = 'We are pleased to offer you the following services:';
		$this->default_offer_footer = 'This offer is valid until '.date('Y-m-d',time()+14*24*60*60).'.';
		$this->offer_mail_text = "Dear ladies and gentleman,\nyou can find our offer attached to this email. To read it, you will need a PDF viewer.\n\nBest wishes,\n?";

		$this->confirmation_prefix = 'B';
		$this->confirmation_suffix = '';
		$this->confirmation_number = 1;
		$this->default_confirmation_header = 'We are pleased to confirm your order ####:';
		$this->default_confirmation_footer = '';
		$this->confirmation_mail_text = "Dear ladies and gentleman,\nattached to this email, you can find our confirmation for your order. To read it, you will need a PDF viewer.\n\nBest wishes,\n?";
		
		$this->invoice_prefix = 'R';
		$this->invoice_suffix = '';
		$this->invoice_number = 1;
		$this->default_invoice_header = 'We allow us to charge the following items:';
		$this->default_invoice_footer = 'Due and payable without discounts within 30 days of the invoice date.';
		$this->invoice_mail_text = "Dear ladies and gentleman,\nattached to this email, you can find an invoice for your order. To read it, you will need a PDF viewer.\n\nBest wishes,\n?";
		

		$this->reminder_prefix = 'M';
		$this->reminder_suffix = '';
		$this->reminder_number = 1;
		$this->default_reminder_header = '';
		$this->default_reminder_footer = '';
		$this->reminder_mail_text = "Dear ladies and gentleman,\nattached to this email, you can find a reminder for a bill you have not payed, yet. To read it, you will need a PDF viewer.\n\nBest wishes,\n?";
	}
	
	static function load($company){
		$company_id = is_array($company) ? $company['id'] : $company;
		$companySettings = new CompanySettings($company_id);
		$db = get_or_create_db();
		$sql = 'SELECT * FROM company_settings WHERE company_id = :cid';
		$args = [':cid'=>$company_id];
		$query = $db->prepare($sql);
		assert($query->execute($args),'Was not able to load settings for the selected company.');
		$rows = $query->fetchAll(PDO::FETCH_ASSOC);
		foreach ($rows as $row) $companySettings->patch($row);
		$companySettings->dirty = [];
		return $companySettings;		
	}
	
	function patch($data = array()){
		if (!isset($this->dirty)) $this->dirty = [];
		foreach ($data as $key => $val){
			if (!isset($this->{$key}) || $this->{$key} != $val) $this->dirty[] = $key;
			$this->{$key} = $val;
		}
		return $this;
	}
	
	function applyTo(Invoice $invoice){
		//debug($invoice,1);		
		$invoice->company_id = $this->company_id;

		switch ($invoice->type){
			case Invoice::TYPE_OFFER:
				$invoice->number = $this->offer_prefix.$this->offer_number.$this->offer_suffix;
				$this->patch(['offer_number'=>$this->offer_number+1]);
				$invoice->head = $this->default_offer_header;
				$invoice->footer = $this->default_offer_footer;
				break;
			case Invoice::TYPE_CONFIRMATION:
				$invoice->number = $this->confirmation_prefix.$this->confirmation_number.$this->confirmation_suffix;
				$this->patch(['confirmation_number'=>$this->confirmation_number+1]);
				$invoice->head = $this->default_confirmation_header;
				$invoice->footer = $this->default_confirmation_footer;
				break;
			case Invoice::TYPE_INVOICE:
				$invoice->number = $this->invoice_prefix.$this->invoice_number.$this->invoice_suffix;
				$this->patch(['invoice_number'=>$this->invoice_number+1]);
				$invoice->head = $this->default_invoice_header;
				$invoice->footer = $this->default_invoice_footer;
				break;
			case Invoice::TYPE_REMINDER:
				$invoice->number = $this->reminder_prefix.$this->reminder_number.$this->reminder_suffix;
				$this->patch(['reminder_number'=>$this->reminder_number+1]);
				$invoice->head = $this->default_reminder_header;
				$invoice->footer = $this->default_reminder_footer;
				break;
		}		
	}
	
	static function table(){
		return [
			'company_id'				=> ['INTEGER','KEY'=>'PRIMARY'],
			
			'default_offer_header' 			=> 'TEXT',
			'default_offer_footer'			=> 'TEXT',
			'offer_prefix'				=> ['TEXT','DEFAULT'=>'A'],
			'offer_suffix'				=> ['TEXT','DEFAULT'=>null],
			'offer_number'				=> ['INT','NOT NULL','DEFAULT 1'],
			'offer_mail_text'			=> 'TEXT',
			
			'default_confirmation_header'	 	=> 'TEXT',
			'default_confirmation_footer'		=> 'TEXT',
			'confirmation_prefix'			=> ['TEXT','DEFAULT'=>'B'],
			'confirmation_suffix'			=> ['TEXT','DEFAULT'=>null],
			'confirmation_number'			=> ['INT','NOT NULL','DEFAULT 1'],
			'confirmation_mail_text'		=> 'TEXT',
			
			'default_invoice_header' 		=> 'TEXT',
			'default_invoice_footer'		=> 'TEXT',
			'invoice_prefix'			=> ['TEXT','DEFAULT'=>'R'],
			'invoice_suffix'			=> ['TEXT','DEFAULT'=>null],
			'invoice_number'			=> ['INT','NOT NULL','DEFAULT 1'],
			'invoice_mail_text'			=> 'TEXT',
				
			'default_reminder_header' 		=> 'TEXT',
			'default_reminder_footer'		=> 'TEXT',
			'reminder_prefix'			=> ['TEXT','DEFAULT'=>'M'],
			'reminder_suffix'			=> ['TEXT','DEFAULT'=>null],
			'reminder_number'			=> ['INT','NOT NULL','DEFAULT 1'],
			'reminder_mail_text'			=> 'TEXT',
		];
	}
	
	public function save(){
		$db = get_or_create_db();
		$query = $db->prepare('SELECT count(*) AS count FROM company_settings WHERE company_id = :cid');
		assert($query->execute([':cid'=>$this->company_id]),'Was not able to count settings for company!');
		$count = reset($query->fetch(PDO::FETCH_ASSOC));
		if ($count == 0){ // new!
			$known_fields = array_keys(CompanySettings::table());
			$fields = [];
			$args = [];
			foreach ($known_fields as $f){
				if (isset($this->{$f})){
					$fields[]=$f;
					$args[':'.$f] = $this->{$f};
				}
			}
			$sql = 'INSERT INTO company_settings ( '.implode(', ',$fields).' ) VALUES ( :'.implode(', :',$fields).' )';
			$query = $db->prepare($sql);			
			assert($query->execute($args),'Was not able to insert new row into company_settings');
		} else {
			if (!empty($this->dirty)){
				$sql = 'UPDATE company_settings SET';
				$args = [':cid'=>$this->company_id];
				foreach ($this->dirty as $field){
					$sql .= ' '.$field.'=:'.$field.',';
					$args[':'.$field] = $this->{$field};
				}
				$sql = rtrim($sql,',').' WHERE company_id = :cid';
				$query = $db->prepare($sql);
				assert($query->execute($args),'Was no able to update company_settings in database!');
			}
		}
	}
	
	function updateFrom(Invoice $invoice){
		$type = '';
		switch ($invoice->type){
			case Invoice::TYPE_INVOICE:
				$type = 'invoice';
				break;
			case Invoice::TYPE_OFFER:
				$type = 'offer';
				break;
			case Invoice::TYPE_CONFIRMATION:
				$type = 'confirmation';
				break;
			case Invoice::TYPE_REMINDER:
				$type = 'reminder';
				break;
		}
		$prefix = preg_replace('/[1-9]+\w*$/', '', $invoice->number);
		$suffix = preg_replace('/^\w*\d+/', '', $invoice->number);
		$number = substr($invoice->number,strlen($prefix),strlen($invoice->number)-strlen($prefix)-strlen($suffix))+1;				
		$data = [
			'default_'.$type.'_header' => $invoice->head,
			'default_'.$type.'_footer' => $invoice->footer,			
			$type.'_prefix' => $prefix,
			$type.'_suffix' => $suffix,
			$type.'_number' => max($number,$this->{$type.'_number'}),	
		];
		$this->patch($data);
		$this->save();
	}
}

class Invoice {
	const STATE_NEW = 1;
	const STATE_SENT = 2;
	const STATE_DELAYED = 3;
	const STATE_PAYED = 4;
	const STATE_ERROR = 99;
	
	const TYPE_OFFER = 1;
	const TYPE_CONFIRMATION = 2;
	const TYPE_INVOICE = 3;
	const TYPE_REMINDER = 4;
	
	function __construct(array $company = []){
		if (isset($company['id'])) $this->company_id = $company['id'];
		if (isset($company['currency'])) $this->currency = $company['currency'];
		$this->state = static::STATE_NEW;
		$this->date = time();
	}
	
	function derive(){
		$new_invoice = new Invoice();
		switch ($this->type){
			case Invoice::TYPE_OFFER:
				$new_invoice->type = Invoice::TYPE_CONFIRMATION;
				break;
			case Invoice::TYPE_CONFIRMATION:
				$new_invoice->type = Invoice::TYPE_INVOICE;
				break;
			default:
				$new_invoice->type = Invoice::TYPE_REMINDER;
		}
		$company_settings = CompanySettings::load($this->company_id);
		$company_settings->applyTo($new_invoice);
		foreach ($this as $field => $value){
			if (!isset($new_invoice->{$field})) $new_invoice->{$field} = $value;	
		}
		unset($new_invoice->id);
		
		$new_invoice->save();
		$company_settings->save();
		
		foreach ($this->positions() as $position) $new_position = $position->copy($new_invoice);
		
		return $new_invoice;
	}

	static function states(){
		return [
			static::STATE_NEW => 'new',
			static::STATE_SENT => 'sent',
			static::STATE_DELAYED => 'delayed',
			static::STATE_PAYED => 'payed',
			static::STATE_ERROR => 'error',
		];
	}
	
	static function table(){
		return [
			'id'				=> ['INTEGER','KEY'=>'PRIMARY'],
			'date'				=> ['TIMESTAMP','NOT NULL'],
			'number'			=> ['TEXT','NOT NULL'],
			'delivery_date'		=> ['VARCHAR'=>100],
			'head'				=> 'TEXT',
			'footer'			=> 'TEXT',
			'company_id'		=> ['INT','NOT NULL'],
			'currency'			=> ['VARCHAR'=>10,'NOT NULL'],
			'template_id'		=> ['INT','NOT NULL'],
			'state'				=> ['INT','NOT NULL','DEFAULT'=>static::STATE_NEW],
			'sender'			=> ['TEXT','NOT NULL'],
			'tax_number'		=> ['VARCHAR'=>255],
			'bank_account'		=> 'TEXT',
			'court'				=> 'TEXT',
			'customer'			=> 'TEXT',
			'customer_number'	=> 'INT',
			'customer_email'	=> ['VARCHAR'=>255],
			'type'				=> ['INT','NOT NULL','DEFAULT'=>static::TYPE_INVOICE],
		];
	}

	function patch($data = array()){
		if (!isset($this->dirty)) $this->dirty = [];
		foreach ($data as $key => $val){
			if ($key === 'id' && isset($this->id)) continue;
			if (!isset($this->{$key}) || $this->{$key} != $val) $this->dirty[] = $key;
			$this->{$key} = $val;
		}
	}

	static function load($options = []){
		$db = get_or_create_db();
		$user_companies = request('company','json');
		$user_company_ids = array_keys($user_companies);

		$args = [];
		if ($user_company_ids !== null){
			if (!is_array($user_company_ids)) $user_company_ids = [ $user_company_ids ];
			$qmarks = str_repeat('?,', count($user_company_ids) - 1) . '?';
			$args = $user_company_ids;			
		}
		$sql = 'SELECT * FROM invoices WHERE company_id IN ('.$qmarks.')';

		$single = false;
		if (isset($options['ids'])){
			$ids = $options['ids'];
			if (!is_array($ids)) {
				$ids = [ $ids ];
				$single = true;
			}
			$qmarks = str_repeat('?,', count($ids) - 1) . '?';
			$args = array_merge($args, $ids);
			$sql .= ' AND id IN ('.$qmarks.')';			
		}
		
		if (isset($options['times'])){
			$tids = $options['times'];
			if (!is_array($tids)) $tids = [$tids];
			$qmarks = str_repeat('?,', count($tids) - 1) . '?';
			$args = array_merge($args, $tids);
			$sql .= ' AND id IN (SELECT invoice_id FROM invoice_positions WHERE time_id IN ('.$qmarks.'))';				
		}
		
		$sql .= ' ORDER BY id DESC';
		
		$query = $db->prepare($sql);
		assert($query->execute($args),'Was not able to load invoices!');
		$rows = $query->fetchAll(PDO::FETCH_ASSOC);
		$invoices = [];
		foreach ($rows as $row){
			$invoice = new Invoice();
			$invoice->patch($row,false);
			//$invoice->company = $user_companies[$invoice->company_id];
			$invoice->dirty = [];
			$invoices[$row['id']] = $invoice;
		}
		return $invoices;
	}
	
	public function save(){
		global $user,$services;
		$db = get_or_create_db();
		if (isset($this->id)){
			if (!empty($this->dirty)){
				$sql = 'UPDATE invoices SET';
				$args = [':id'=>$this->id];
				foreach ($this->dirty as $field){
					$sql .= ' '.$field.'=:'.$field.',';
					$args[':'.$field] = $this->{$field};
				}
				$sql = rtrim($sql,',').' WHERE id = :id';
				$query = $db->prepare($sql);
				assert($query->execute($args),'Was no able to update invoice in database!');
				if (in_array('state',$this->dirty) && isset($services['time'])){
					$time_ids = [];
					foreach ($this->positions() as $position){
						if (isset($position->time_id) && $position->time_id !== null) $time_ids[] = $position->time_id;
					}
					if (!empty($time_ids)){
						$state = null;
						switch ($this->state){
							case static::STATE_NEW:
							case static::STATE_SENT:
							case static::STATE_DELAYED:
								$state = 'PENDING';
								break;
							case static::STATE_PAYED:
								$state = 'COMPLETED';
								break;
							default:
								$state = 'OPEN';
						}
						request('time','update_state',[$state=>implode(',',$time_ids)]);
					}
				}
				$this->dirty = [];
			}
		} else {
			if (!isset($this->date)) $this->date = time();
			$known_fields = array_keys(Invoice::table());
			$fields = [];
			$args = [];
			foreach ($known_fields as $f){
				if (isset($this->{$f})){
					$fields[]=$f;
					$args[':'.$f] = $this->{$f};
				}
			}			
			$sql = 'INSERT INTO invoices ( '.implode(', ',$fields).' ) VALUES ( :'.implode(', :',$fields).' )';
			$query = $db->prepare($sql);
			assert($query->execute($args),'Was not able to insert new invoice');	
			$this->id = $db->lastInsertId();
		}
		
		if (isset($services['bookmark']) && ($raw_tags = param('tags'))){
			$raw_tags = explode(' ', str_replace(',',' ',$raw_tags));
			$tags = [];
			foreach ($raw_tags as $tag){
				if (trim($tag) != '') $tags[]=$tag;
			}
			request('bookmark','add',['url'=>getUrl('invoice').$this->id.'/view','comment'=>t('Document ?',$this->number),'tags'=>$tags]);
		}
	}
	
	public function company(){
		if (!isset($this->company)){
			$this->company = request('company','json',['ids'=>$this->company_id,'single'=>true]);
		}
		return $this->company;
	}
	
	public function company_settings(){
		if (!isset($this->company_settings)){
			$this->company_settings = CompanySettings::load($this->company_id);
		}
		return $this->company_settings;
	}
	
	public function mail_text(){
		switch ($this->type){
			case Invoice::TYPE_OFFER:
				return $this->company_settings()->offer_mail_text;
			case Invoice::TYPE_CONFIRMATION:
				return $this->company_settings()->confirmation_mail_text;
			case Invoice::TYPE_INVOICE:
				return $this->company_settings()->invoice_mail_text;
			case Invoice::TYPE_REMINDER:
				return $this->company_settings()->reminder_mail_text;
		}
		return 'not implemented';
	}
	
	public function update_mail_text($new_text){
		$settings = $this->company_settings();
		switch ($this->type){			
			case Invoice::TYPE_OFFER:
				$settings->patch(['offer_mail_text'=>$new_text]);
				break;
			case Invoice::TYPE_CONFIRMATION:
				$settings->patch(['confirmation_mail_text'=>$new_text]);
				break;
			case Invoice::TYPE_INVOICE:
				$settings->patch(['invoice_mail_text'=>$new_text]);
				break;
			case Invoice::TYPE_REMINDER:
				$settings>patch(['reminder_mail_text'=>$new_text]);
				break;
		}
		$settings->save();
	}
	
	
	public function date(){
		return date('d.m.Y',$this->date);
	}
	
	public function delivery_date(){
		if (!isset($this->delivery_date) || $this->delivery_date === null) return '';
		return $this->delivery_date;
	}
	
	
	public function state(){
		if (array_key_exists($this->state, Invoice::states())) return Invoice::states()[$this->state];
		return t('unknown state');
	}
	
	public function customer_short(){
		return reset(explode("\n",$this->customer));
	}
	
	public function positions(){
		if (!isset($this->positions)) $this->positions = InvoicePosition::load($this);
		return $this->positions;
	}
	
	function add_position($code,$title,$description,$amount,$unit,$price,$tax){
		$db = get_or_create_db();
	
		$query = $db->prepare('SELECT MAX(pos) FROM invoice_positions WHERE invoice_id = :id');
		assert($query->execute(array(':id'=>$invoice_id)),'Was not able to get last invoice position!');
		$row = $query->fetch(PDO::FETCH_COLUMN);
		$pos = ($row === null)?1:$row+1;
	
		$query = $db->prepare('INSERT INTO invoice_positions (invoice_id, pos, item_code, amount, unit, title, description, single_price, tax) VALUES (:id, :pos, :code, :amt, :unit, :ttl, :desc, :price, :tax)');
		$args = array(':id'=>$invoice_id,':pos'=>$pos,':code'=>$code,':amt'=>$amount,':unit'=>$unit,':ttl'=>$title,':desc'=>$description,':price'=>$price,':tax'=>$tax);
		assert($query->execute($args),'Was not able to store new postion for invoice '.$invoice_id.'!');
	}
	
	function elevate($position_number){
		if ($position_number<2) return;
		$positions = $this->positions();
		$a = $this->positions[$position_number]->delete();
		$b = $this->positions[$position_number-1]->delete();
		$a->patch(['pos'=>$position_number-1])->save();
		$b->patch(['pos'=>$position_number])->save();
	}
	
	function sum(){
		$sum = 0;
		foreach ($this->positions() as $position){
			$pos = $position->amount * $position->single_price;
			$sum += $pos + ($pos*$position->tax/100.0);
		}
		return round($sum/100.0,2);
	}
	
	function template(){
		if (!isset($this->template_id) || $this->template_id === null || $this->template_id < 1) return null;
		$templates = Template::load($this->company_id);
		if (!isset($templates[$this->template_id])) return null;
		return $templates[$this->template_id];
	}
}

class Template{
	static function table(){
		return [
			'id'						=> ['INTEGER','KEY'=>'PRIMARY'],
			'company_id'			 	=> ['INT','NOT NULL'],
			'name'						=> ['VARCHAR'=>255,'NOT NULL'],
			'template'					=> 'BLOB',
		];
	}

	static function load($company_id){
		$templates = [];
		$db = get_or_create_db();
		$sql = 'SELECT * FROM templates WHERE company_id = :cid';
		$args = [':cid'=>$company_id];
		$query = $db->prepare($sql);
		assert($query->execute($args),'Was not able to templates for the selected company.');
		$rows = $query->fetchAll(PDO::FETCH_ASSOC);
		foreach ($rows as $row) {
			$template = new Template();
			$template->patch($row);
			$template->dirty = [];
			$templates[$template->id] = $template;
		}
		return $templates;
	}

	function __construct($file_path = null){
		global $services;
		if ($file_path !== null && isset($services['files'])) {
			$this->template = request('files','download?file='.$file_path,null,false,NO_CONVERSSION);
		}
	}

	function patch($data = array()){
		if (!isset($this->dirty)) $this->dirty = [];
		foreach ($data as $key => $val){
			if ($key === 'id' && isset($this->id)) continue;
			if (!isset($this->{$key}) || $this->{$key} != $val) $this->dirty[] = $key;
			$this->{$key} = $val;
		}
	}

	public function save(){
		global $user;
		$db = get_or_create_db();
		if (isset($this->id)){
			if (!empty($this->dirty)){
				$sql = 'UPDATE templates SET';
				$args = [':id'=>$this->id];
				foreach ($this->dirty as $field){
					$sql .= ' '.$field.'=:'.$field.',';
					$args[':'.$field] = $this->{$field};
				}
				$sql = rtrim($sql,',').' WHERE id = :id';
				$query = $db->prepare($sql);
				assert($query->execute($args),'Was no able to update template in database!');
			}
		} else {
			$known_fields = array_keys(Template::table());
			$fields = [];
			$args = [];
			foreach ($known_fields as $f){
				if (isset($this->{$f})){
					$fields[]=$f;
					$args[':'.$f] = $this->{$f};
				}
			}
			$sql = 'INSERT INTO templates ( '.implode(', ',$fields).' ) VALUES ( :'.implode(', :',$fields).' )';
			$query = $db->prepare($sql);
			assert($query->execute($args),'Was not able to insert new template');
			$this->id = $db->lastInsertId();
		}
	}

	public function file(){
		$tempfile = tempnam('/tmp','template_');
		$f = fopen($tempfile,'w');
		fwrite($f,$this->template);
		fclose($f);
		return $tempfile;
	}
}
?>
