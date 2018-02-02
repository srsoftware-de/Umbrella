<?php
function get_or_create_db(){
	if (!file_exists('db')) assert(mkdir('db'),'Failed to create document/db directory!');
	assert(is_writable('db'),'Directory document/db not writable!');
	if (!file_exists('db/documents.db')){
		$db = new PDO('sqlite:db/documents.db');

		$tables = [
			'documents'=>Document::table(),
			'document_positions'=>DocumentPosition::table(),
			'document_types'=>DocumentType::table(),
			'company_settings'=>CompanySettings::table(),
			'customer_prices'=>CustomerPrice::table(),
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
								assert($prop_v === 'PRIMARY','Non-primary keys not implemented in document/controller.php!');
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
		$db = new PDO('sqlite:db/documents.db');
	}
	return $db;
}

class CustomerPrice{
	static function table(){
		return [
			'company_id'	=> ['INTEGER','NOT NULL'],
			'customer_number'	=> ['VARCHAR'=>255],
			'item_code'		=> ['VARCHAR'=>50],
			'single_price'	=> 'INTEGER',
		];
	}
}

class DocumentPosition{
	static function table(){	
		return [
			'document_id'	=> ['INTEGER','NOT NULL'],
			'pos'			=> ['INTEGER','NOT NULL'],
			'item_code'		=> ['VARCHAR'=>50],
			'amount'		=> ['INTEGER','NOT NULL','DEFAULT'=>1],
			'unit'			=> ['VARCHAR'=> 12],
			'title'			=> ['VARCHAR'=>255],
			'description'	=> 'TEXT',
			'single_price'	=> 'INTEGER',
			'tax'			=> 'INTEGER',
			'time_id'		=> 'INTEGER',
		];
	}

	function __construct(Document $document){
		$db = get_or_create_db();

		$query = $db->prepare('SELECT max(pos) AS pos FROM document_positions WHERE document_id = :iid');
		assert($query->execute([':iid'=>$document->id]),'Was not able to read document position table');
		$this->pos = reset($query->fetch(PDO::FETCH_ASSOC)) +1;
		$this->document_id = $document->id;
	}

	public function copy(Document $document){
		$new_position = new DocumentPosition($document);
		foreach ($this as $field => $value){
			if (in_array($field, ['id','document_id'])) continue;
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
		$query = $db->prepare('SELECT count(*) AS count FROM document_positions WHERE document_id = :iid AND pos = :pos');
		assert($query->execute([':iid'=>$this->document_id,':pos'=>$this->pos]),'Was not able to read from document positions table!');
		$count = reset($query->fetch(PDO::FETCH_ASSOC));
		if ($count == 0){ // new!
			$known_fields = array_keys(DocumentPosition::table());
			$fields = [];
			$args = [];
			foreach ($known_fields as $f){
				if (isset($this->{$f})){
					$fields[]=$f;
					$args[':'.$f] = $this->{$f};
				}
			}
			$sql = 'INSERT INTO document_positions ( '.implode(', ',$fields).' ) VALUES ( :'.implode(', :',$fields).' )';
			$query = $db->prepare($sql);
			assert($query->execute($args),'Was not able to insert new row into document_positions');
		} else {
			if (!empty($this->dirty)){
				$sql = 'UPDATE document_positions SET';
				$args = [':iid'=>$this->document_id,':pos'=>$this->pos];
				foreach ($this->dirty as $field){
					$sql .= ' '.$field.'=:'.$field.',';
					$args[':'.$field] = $this->{$field};
				}
				$this->dirty = [];
				$sql = rtrim($sql,',').' WHERE document_id = :iid AND pos = :pos';
				$query = $db->prepare($sql);
				assert($query->execute($args),'Was no able to update document_positions in database!');
			}
		}
		
		return $this;
	}
	
	static function load($document){
		$db = get_or_create_db();
		$sql = 'SELECT pos,* FROM document_positions WHERE document_id = :iid ORDER BY pos';
		$args = [':iid'=>$document->id];
		$query = $db->prepare($sql);
		assert($query->execute($args),'Was not able to load invoie positions.');
		$rows = $query->fetchAll(INDEX_FETCH);
		$result = [];
		foreach ($rows as $pos => $row){
			$documentPosition = new DocumentPosition($document);
			$documentPosition->patch($row);
			$documentPosition->dirty = [];
			$result[$pos] = $documentPosition;
		}
		return $result;
	}
	
	public function delete(){
		global $services;
		$db = get_or_create_db();
		$query = $db->prepare('DELETE FROM document_positions WHERE document_id = :iid AND pos = :pos');
		assert($query->execute([':iid'=>$this->document_id,':pos'=>$this->pos]),'Was not able to remove entry from document positions table!');
		
		$query = $db->prepare('UPDATE document_positions SET pos = pos-1 WHERE document_id = :iid AND pos > :pos');
		assert($query->execute([':iid'=>$this->document_id,':pos'=>$this->pos]));
		if (isset($this->time_id) && $this->time_id !== null && isset($services['time'])){
			request('time','update_state',['OPEN'=>$this->time_id]);
		}

		return $this;
	}
	
}

class CompanySettings{
	function __construct($company_id,$doc_type_id){
		$this->company_id = $company_id;
		$this->document_type_id = $doc_type_id;
		$this->default_header = 'Please enter a new header.';
		$this->default_footer = 'Please enter a new footer';
		$this->type_prefix = '[[';
		$this->type_suffix = ']]';
		$this->type_number = 1;
		$this->type_mail_text = "Dear Ladies and Gentlemen,\n\nAttached to this mail you will find a new ? document. To open it, you need a pdf viewer.";
	}
	
	static function load($company,$doc_type_id){
		$company_id = is_array($company) ? $company['id'] : $company;
		$companySettings = new CompanySettings($company_id,$doc_type_id);
		$db = get_or_create_db();
		$sql = 'SELECT * FROM company_settings WHERE company_id = :cid and document_type_id = :tid';
		$args = [':cid'=>$company_id, ':tid'=>$doc_type_id];
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
	
	function applyTo(Document $document){
		//debug($document,1);		
		$document->company_id = $this->company_id;

		$document->number = $this->type_prefix.$this->type_number.$this->type_suffix;
		$this->patch(['type_number'=>$this->type_number+1]);
		$document->head = $this->default_header;
		$document->footer = $this->default_footer;
	}
	
	static function table(){
		return [
			'company_id'				=> ['INTEGER','KEY'=>'PRIMARY'],
			'document_type_id'			=> ['INT','NOT NULL'],
			'default_header' 			=> 'TEXT',
			'default_footer'			=> 'TEXT',
			'type_prefix'				=> ['TEXT','DEFAULT'=>'A'],
			'type_suffix'				=> ['TEXT','DEFAULT'=>null],
			'type_number'				=> ['INT','NOT NULL','DEFAULT 1'],
			'type_mail_text'			=> 'TEXT',
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
	
	function updateFrom(Document $document){
		$type = '';
		$prefix = preg_replace('/[1-9]+\w*$/', '', $document->number);
		$suffix = preg_replace('/^\w*\d+/', '', $document->number);
		$number = substr($document->number,strlen($prefix),strlen($document->number)-strlen($prefix)-strlen($suffix))+1;				
		$data = [
			'default_header' => $document->head,
			'default_footer' => $document->footer,			
			'type_prefix' => $prefix,
			'type_suffix' => $suffix,
			'type_number' => max($number,$this->{'type_number'}),	
		];
		$this->patch($data);
		$this->save();
	}
}

class Document {
	const STATE_NEW = 1;
	const STATE_SENT = 2;
	const STATE_DELAYED = 3;
	const STATE_PAYED = 4;
	const STATE_ERROR = 99;
	
	function __construct(array $company = []){
		if (isset($company['id'])) $this->company_id = $company['id'];
		if (isset($company['currency'])) $this->currency = $company['currency'];
		$this->state = static::STATE_NEW;
		$this->date = time();
	}
	
	function type(){
		if (!isset($this->type)) $this->type = DocumentType::load(['ids' => $this->type_id]);
		return $this->type;
	}
	
	function derive(){
		$new_document = new Document();
		
		if ($next_type_id = $this->type()->next_type_id){
			$new_document->type_id = $next_type_id;
		} else {
			error('No successor type defined for documents of type ?',$this->type()->name);
			redirect(getUrl('document'));
			die();
		}
		
		$company_settings = CompanySettings::load($this->company_id,$next_type_id);
		$company_settings->applyTo($new_document);
		foreach ($this as $field => $value){
			if (array_key_exists($field,Document::table())  && !isset($new_document->{$field})) $new_document->{$field} = $value;	
		}
		unset($new_document->id);
		$new_document->save();
		$company_settings->save();

		foreach ($this->positions() as $position) $new_position = $position->copy($new_document);

		return $new_document;
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
			'type_id'			=> ['INT','NOT NULL'],
			'company_id'		=> ['INT','NOT NULL'],
			'number'			=> ['TEXT','NOT NULL'],
			'date'				=> ['TIMESTAMP','NOT NULL'],
			'state'				=> ['INT','NOT NULL','DEFAULT'=>static::STATE_NEW],
			'template_id'		=> ['INT','NOT NULL'],
			'delivery_date'		=> ['VARCHAR'=>100],
			'head'				=> 'TEXT',
			'footer'			=> 'TEXT',
			'currency'			=> ['VARCHAR'=>10,'NOT NULL'],
			
			'sender'			=> ['TEXT','NOT NULL'],
			'tax_number'		=> ['VARCHAR'=>255],
			'bank_account'		=> 'TEXT',
			'court'				=> 'TEXT',
			
			'customer'			=> 'TEXT',
			'customer_number'	=> ['VARCHAR'=>255],
			'customer_tax_number'=> ['VARCHAR'=>255],
			'customer_email'	=> ['VARCHAR'=>255],
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
		$sql = 'SELECT * FROM documents WHERE company_id IN ('.$qmarks.')';

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
			$sql .= ' AND id IN (SELECT document_id FROM document_positions WHERE time_id IN ('.$qmarks.'))';
		}
		
		$sql .= ' ORDER BY ';
		if (isset($options['order']) && array_key_exists($options['order'],Document::table())){
			$sql .= $options['order'].' DESC, ';
		}
		$sql .= 'id DESC';

		$query = $db->prepare($sql);
		assert($query->execute($args),'Was not able to load documents!');
		$rows = $query->fetchAll(PDO::FETCH_ASSOC);
		$documents = [];
		foreach ($rows as $row){
			$document = new Document();
			$document->patch($row,false);
			//$document->company = $user_companies[$document->company_id];
			$document->dirty = [];
			$documents[$row['id']] = $document;
		}
		return $documents;
	}

	public function save(){
		global $user,$services;
		$db = get_or_create_db();
		if (isset($this->id)){
			if (!empty($this->dirty)){
				$sql = 'UPDATE documents SET';
				$args = [':id'=>$this->id];
				foreach ($this->dirty as $field){
					$sql .= ' '.$field.'=:'.$field.',';
					$args[':'.$field] = $this->{$field};
				}
				$sql = rtrim($sql,',').' WHERE id = :id';
				$query = $db->prepare($sql);
				assert($query->execute($args),'Was no able to update document in database!');
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
			$known_fields = array_keys(Document::table());
			$fields = [];
			$args = [];
			foreach ($known_fields as $f){
				if (isset($this->{$f})){
					$fields[]=$f;
					$args[':'.$f] = $this->{$f};
				}
			}			
			$sql = 'INSERT INTO documents ( '.implode(', ',$fields).' ) VALUES ( :'.implode(', :',$fields).' )';
			$query = $db->prepare($sql);
			debug(query_insert($query, $args));
			assert($query->execute($args),'Was not able to insert new document');	
			$this->id = $db->lastInsertId();
		}
		
		if (isset($services['bookmark']) && ($raw_tags = param('tags'))){
			$raw_tags = explode(' ', str_replace(',',' ',$raw_tags));
			$tags = [];
			foreach ($raw_tags as $tag){
				if (trim($tag) != '') $tags[]=$tag;
			}
			request('bookmark','add',['url'=>getUrl('document').$this->id.'/view','comment'=>t('Document ?',$this->number),'tags'=>$tags]);
		}
	}
	
	public function company($field = null){
		if (!isset($this->company)) $this->company = request('company','json',['ids'=>$this->company_id,'single'=>true]);
		if ($field !== null) return $this->company[$field];
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
			case Document::TYPE_OFFER:
				return $this->company_settings()->offer_mail_text;
			case Document::TYPE_CONFIRMATION:
				return $this->company_settings()->confirmation_mail_text;
			case Document::TYPE_INVOICE:
				return $this->company_settings()->document_mail_text;
			case Document::TYPE_REMINDER:
				return $this->company_settings()->reminder_mail_text;
		}
		return 'not implemented';
	}

	public function update_mail_text($new_text){
		$settings = $this->company_settings();
		switch ($this->type){
			case Document::TYPE_OFFER:
				$settings->patch(['offer_mail_text'=>$new_text]);
				break;
			case Document::TYPE_CONFIRMATION:
				$settings->patch(['confirmation_mail_text'=>$new_text]);
				break;
			case Document::TYPE_INVOICE:
				$settings->patch(['document_mail_text'=>$new_text]);
				break;
			case Document::TYPE_REMINDER:
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
		if (array_key_exists($this->state, Document::states())) return Document::states()[$this->state];
		return t('unknown state');
	}
	
	public function customer_short(){
		return reset(explode("\n",$this->customer));
	}
	
	public function positions(){
		if (!isset($this->positions)) $this->positions = DocumentPosition::load($this);
		return $this->positions;
	}
	
	function add_position($code,$title,$description,$amount,$unit,$price,$tax){
		$db = get_or_create_db();
	
		$query = $db->prepare('SELECT MAX(pos) FROM document_positions WHERE document_id = :id');
		assert($query->execute(array(':id'=>$document_id)),'Was not able to get last document position!');
		$row = $query->fetch(PDO::FETCH_COLUMN);
		$pos = ($row === null)?1:$row+1;
	
		$query = $db->prepare('INSERT INTO document_positions (document_id, pos, item_code, amount, unit, title, description, single_price, tax) VALUES (:id, :pos, :code, :amt, :unit, :ttl, :desc, :price, :tax)');
		$args = array(':id'=>$document_id,':pos'=>$pos,':code'=>$code,':amt'=>$amount,':unit'=>$unit,':ttl'=>$title,':desc'=>$description,':price'=>$price,':tax'=>$tax);
		assert($query->execute($args),'Was not able to store new postion for document '.$document_id.'!');
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

class DocumentType{
	static function table(){
		return [
			'id'						=> ['INTEGER','KEY'=>'PRIMARY'],
			'next_type_id'			 	=> ['INT'],
			'name'						=> ['VARCHAR'=>255,'NOT NULL'],
		];
	}
	
	static function addBasicTypes(){
		$db = get_or_create_db();
		$db->exec('INSERT INTO document_types (id, next_type_id, name) VALUES (1, 2, "offer"), (2, 3, "confirmation"), (3, 4, "invoice"), (4, 4, "reminder")');
	}

	static function load($options = []){
		$types = [];
		$db = get_or_create_db();
		$sql = 'SELECT * FROM document_types';
		$args = [];
		$single = false;
		
		if (isset($options['ids'])){
			$ids = $options['ids'];
			if (!is_array($ids)) {
				$ids = [$ids];
				$single = true;
			}
			$qMarks = str_repeat('?,', count($ids)-1).'?';
			$sql .= ' WHERE id IN ('.$qMarks.')';
			$args = array_merge($args, $ids);
		}
		
		$query = $db->prepare($sql);
		assert($query->execute($args),'Was not able to read document types.');
		$rows = $query->fetchAll(PDO::FETCH_ASSOC);		
		foreach ($rows as $row) {
			$doc_type = new DocumentType();
			$doc_type->patch($row);
			$doc_type->dirty = [];
			if ($single) return $doc_type;
			$types[$doc_type->id] = $doc_type;
		}
		if (empty($types)) {
			DocumentType::addBasicTypes();
			$types = DocumentType::load();
		}		
		return $types;
	}
	
	function patch($data = array()){
		if (!isset($this->dirty)) $this->dirty = [];
		foreach ($data as $key => $val){
			if ($key === 'id' && isset($this->id)) continue;
			if (!isset($this->{$key}) || $this->{$key} != $val) $this->dirty[] = $key;
			$this->{$key} = $val;
		}
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
		assert($query->execute($args),'Was not able to read templates for the selected company.');
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
