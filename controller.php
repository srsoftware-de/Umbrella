<?php include '../bootstrap.php';

const MODULE = 'Company';
$title = 'Umbrella Company Management';

function get_or_create_db(){
	if (!file_exists('db') && !mkdir('db')) throw new Exception('Failed to create company/db directory!');
	if (!is_writable('db')) throw new Exception('Directory company/db not writable!');
	if (!file_exists('db/companies.db')){
		$db = new PDO('sqlite:db/companies.db');
		$sql = 'CREATE TABLE companies ( ';
		foreach (Company::fields() as $field => $props){
			$sql .= $field . ' ';
			if (is_array($props)){
				foreach ($props as $prop_k => $prop_v){
					switch (true){
						case $prop_k==='VARCHAR':
							$sql.= 'VARCHAR('.$prop_v.') '; break;
						case $prop_k==='DEFAULT':
							$sql.= 'DEFAULT '.($prop_v===null?'NULL':'"'.$prop_v.'"').' '; break;
						case $prop_k==='KEY':
							if ($prop_v != 'PRIMARY') throw new Exception('Non-primary keys not implemented in company/controller.php!');
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
		if (!$db->query($sql)) throw new Exception('Was not able to create companies table in companies.db!');
		if (!$db->query('CREATE TABLE companies_users (company_id INT NOT NULL, user_id INT NOT NULL)')) throw new Exception('Was not able to create table companies_users.');
	} else {
		$db = new PDO('sqlite:db/companies.db');
	}
	return $db;

}

class Company extends UmbrellaObjectWithId{
	function __construct($name = null){
		if ($name == null) throw new Exception('Company name must not be empty');
		$this->name = $name;
	}

	static function fields(){
		return [
			'id'					=> ['INTEGER','KEY'=>'PRIMARY'],
			'name'					=> ['VARCHAR'=>255, 'NOT NULL'],
			'address'				=> 'TEXT',
			'email'					=> ['VARCHAR'=>255],
			'phone'					=> ['VARCHAR'=>255],
			'bank_account'			=> 'TEXT',
			'court'					=> 'TEXT',
			'currency'				=> ['VARCHAR'=>10,'DEFAULT'=>'€'],
			'logo'					=> 'TEXT',
			'tax_number'			=> ['VARCHAR'=>255],
			'decimals'				=> ['INT','NOT NULL','DEFAULT'=>'2'],
			'decimal_separator'		=> ['VARCHAR'=>10,'DEFAULT'=>','],
			'thousands_separator'	=> ['VARCHAR'=>10,'DEFAULT'=>'.'],
			'last_customer_number'	=> ['INT','DEFAULT'=>NULL],
			'customer_number_prefix'=> ['VARCHAR'=>255],
		];
	}

	public function save(){
		global $user;
		$db = get_or_create_db();
		if (isset($this->id)){
			if (!empty($this->dirty)){
				$sql = 'UPDATE companies SET';
				$args = [':id'=>$this->id];
				foreach ($this->dirty as $field){
					$sql .= ' '.$field.'=:'.$field.',';
					$args[':'.$field] = $this->{$field};
				}
				$sql = rtrim($sql,',').' WHERE id = :id';
				$query = $db->prepare($sql);
				if (!$query->execute($args)) throw new Exception('Was no able to update company in database!');
				redirect('../index');
			}
		} else {
			$known_fields = array_keys(Company::fields());
			$fields = [];
			$args = [];
			foreach ($known_fields as $f){
				if (isset($this->{$f})){
					$fields[]=$f;
					$args[':'.$f] = $this->{$f};
				}
			}
			$query = $db->prepare('INSERT INTO companies ( '.implode(', ',$fields).' ) VALUES ( :'.implode(', :',$fields).' )');
			if (!$query->execute($args)) throw new Exception('Was not able to insert new company');

			$this->id = $db->lastInsertId();
			$query = $db->prepare('INSERT INTO companies_users (company_id, user_id) VALUES (:cid, :uid);');
			if (!$query->execute([':cid'=>$this->id, ':uid'=>$user->id])) throw new Exception('Was no able to assign you to the new company!');
			redirect('index');
		}
	}

	static function connected_users($options = array()){
		global $user;
		$db = get_or_create_db();

		$sql = 'SELECT user_id,user_id FROM companies_users WHERE company_id IN (SELECT company_id FROM companies_users WHERE user_id = ?)';
		$args = [];

		$single = false;
		if (isset($options['ids'])){
			$ids = $options['ids'];
			if (!is_array($ids)){
				$single = true;
				$ids = [$ids];
			}

			if (!empty($ids)){
				$qmarks = str_repeat('?,', count($ids) - 1) . '?';
				$sql .= ' AND id IN ('.$qmarks.')';
				$args = $ids;
			}
		}
		array_unshift($args,$user->id);

		$sql .= ' GROUP BY user_id';
		$query = $db->prepare($sql);
		if (!$query->execute($args)) throw new Exception('Was not able to load companies!');
		return (array_keys($query->fetchAll(INDEX_FETCH)));
	}

	static function load($options = array()){
		global $user;
		$db = get_or_create_db();

		$sql = 'SELECT * FROM companies WHERE id IN (SELECT company_id FROM companies_users WHERE user_id = ?)';
		$args = [];

		$single = false;
		if (isset($options['ids'])){
			$ids = $options['ids'];
			if (!is_array($ids)){
				$single = true;
				$ids = [$ids];
			}

			if (!empty($ids)){
				$qmarks = str_repeat('?,', count($ids) - 1) . '?';
				$sql .= ' AND id IN ('.$qmarks.')';
				$args = $ids;
			}
		}

		array_unshift($args,$user->id);
		$query = $db->prepare($sql);
		if (!$query->execute($args)) throw new Exception('Was not able to load companies!');
		$rows = $query->fetchAll(PDO::FETCH_ASSOC);
		$companies = [];
		$load_users = isset($options['users']) && $options['users'] == true;
		foreach ($rows as $row){
			$company = new Company($row['name']);
			$company->patch($row);
			$company->dirty=[];
			if ($load_users) $company->users();
			if ($single) return $company;
			$companies[$row['id']] = $company;
		}
		return $companies;
	}

	public function users(){
		if (!isset($this->users)){
			$db = get_or_create_db();
			$query = $db->prepare('SELECT user_id FROM companies_users WHERE company_id = :id');
			if (!$query->execute([':id'=>$this->id])) throw new Exception('Was not able to load list of associated users!');
			$this->users = array_keys($query->fetchAll(INDEX_FETCH));
		}
		return $this->users;
	}

	public function drop_user($user_id = null){
		if ($user_id == null) throw new Exception('Trying to drop "null" user from company! Aborting');
		$db = get_or_create_db();
		$query = $db->prepare('DELETE FROM companies_users WHERE company_id = :cid AND user_id = :uid');
		if (!$query->execute([':cid'=>$this->id,':uid'=>$user_id])) throw new Exception('Was not able to remove assignment in database!');
	}

	public function add_user($user_id = null){
		if ($user_id == null) throw new Exception('Trying to assign "null" as user to company! Aborting');
		$db = get_or_create_db();
		$query = $db->prepare('INSERT INTO companies_users (company_id, user_id) VALUES (:cid, :uid)');
		if (!$query->execute([':cid'=>$this->id,':uid'=>$user_id])) throw new Exception('Was not able to assign user in database!');
	}
}

?>
