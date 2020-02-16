<?php include '../bootstrap.php';

	const MODULE = 'User';
	const DB_VERSION = 2;
	$title = 'Umbrella User Management';

	function db_version(){
		$db = get_or_create_db();
		$query = $db->prepare('SELECT value FROM settings WHERE key = "db_version"');
		if (!$query->execute()) throw new Exception(_('Failed to query db_version!'));
		$rows = $query->fetchAll(PDO::FETCH_COLUMN);
		if (empty($rows)) return null;
		return reset($rows);
	}

	function get_or_create_db(){
		$table_filename = 'users.db';
		if (!file_exists('db')) assert(mkdir('db'),'Failed to create user/db directory!');
		assert(is_writable('db'),'Directory user/db not writable!');
		if (!file_exists('db/'.$table_filename)){
			$db = new PDO('sqlite:db/'.$table_filename);

			$tables = [
					'users'=>User::table(),
					'tokens'=>Token::table(),
					'token_uses'=>Token::uses(),
					'login_services'=>LoginService::table(),
					'service_ids_users'=>LoginService::users(),
					'messages'=>Message::table(),
					'recipients'=>Message::recipients()
			];

			foreach ($tables as $table => $fields){
				$sql = 'CREATE TABLE '.$table.' ( ';
				foreach ($fields as $field => $props){
					if ($field == 'UNIQUE'||$field == 'PRIMARY KEY') {
						$field .='('.implode(',',$props).')';
						$props = null;
					}
					$sql .= $field . ' ';
					if (is_array($props)){
						foreach ($props as $prop_k => $prop_v){
							switch (true){
								case $prop_k==='VARCHAR':
									$sql.= 'VARCHAR('.$prop_v.') '; break;
								case $prop_k==='DEFAULT':
									$sql.= 'DEFAULT '.($prop_v === null?'NULL ':'"'.$prop_v.'" '); break;
								case $prop_k==='KEY':
									assert($prop_v === 'PRIMARY','Non-primary keys not implemented in user/controller.php!');
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
				assert($query->execute(),'Was not able to create '.$table.' table in '.$table_filename.'!');
			}

			User::createAdmin();
		} else {
			$db = new PDO('sqlite:db/'.$table_filename);
		}
		return $db;
	}

	function get_themes(){
		$entries = scandir('common_templates/css');
		$results = [];
		foreach ($entries as $entry){
			if (in_array($entry,['.','..'])) continue;
			if (is_dir('common_templates/css/'.$entry)) $results[] = $entry;
		}
		return $results;
	}

	class LoginService extends UmbrellaObjectWithId{
		function assign($foreign_id){
			global $user;
			$db = get_or_create_db();

			$query = $db->prepare('INSERT INTO service_ids_users (service_id, user_id) VALUES (:service, :user);');
			assert($query->execute([':service'=>$this->id.':'.$foreign_id,':user'=>$user->id]),t('Was not able to assign service id (?) with your user account!',$foreign_id));
			info('Your account has been assigned with ◊ / id ◊',[$this->id,$foreign_id]);
		}

		function deassign($foreign_id){
			$db = get_or_create_db();
			$query = $db->prepare('DELETE FROM service_ids_users WHERE service_id = :service;');
			assert($query->execute([':service'=>$foreign_id]),t('Was not able to de-assign service id (?) from your user account!',$foreign_id));
			info('◊ has been de-assigned.',$foreign_id);
		}

		function delete(){
			$db = get_or_create_db();
			$sql = 'DELETE FROM login_services WHERE name = :name';
			$query = $db->prepare($sql);
			if (!$query->execute([':name'=>$this->name])) throw new Exception('Was not able to remove login service "'.$this->id.'"');
		}

		static function load($name = null){
			$db = get_or_create_db();

			$sql = 'SELECT * FROM login_services';
			$args = [];
			if ($name) {
				$sql .= ' WHERE name = :name';
				$args[':name'] = $name;
			}
			$query = $db->prepare($sql);
			assert($query->execute($args),'Was not able to read login services list.');
			$rows = $query->fetchAll(INDEX_FETCH);
			$services = [];
			foreach ($rows as $id => $row){
				$service = new LoginService();
				$service->patch($row);
				$service->name = $id;
				unset($service->dirty);
				if ($name) return $service;
				$services[$id] = $service;
			}
			if ($name) return null;
			return $services;
		}

		function get_user($foreign_id = null){
			if (empty($foreign_id)) throw new Exception('LoginService.login called without an id!');

			$sql = 'SELECT * FROM service_ids_users WHERE service_id = :id';
			$args = [':id'=>$foreign_id];

			$db = get_or_create_db();
			$query = $db->prepare($sql);
			if (!$query->execute($args)) throw new Exception('Was not able to read list of assigned logins.');
			$row = $query->fetch(INDEX_FETCH);
			if (empty($row)) throw new Exception('Id unknown to service_ids_users!');
			return User::load(['ids'=>$row['user_id']]);
		}

		function save(){
			$sql = 'INSERT INTO login_services ';
			$keys = [];
			$args = [];
			foreach (LoginService::table() as $key =>$dummy){
				if (in_array($key, $this->dirty)){
					$keys[] = $key;
					$args[':'.$key] = $this->{$key};
				}
			}

			$sql .= '('.implode(', ',$keys).') VALUES (:'.implode(', :',$keys).')';
			$db = get_or_create_db();
			$query = $db->prepare($sql);
			if (!$query->execute($args)) throw new Exception('Was not able to create new login service');
		}

		static function table(){
			return [
					'name'=>['VARCHAR'=>255,'KEY'=>'PRIMARY'],
					'url'=>'TEXT',
					'client_id'=>['VARCHAR'=>255],
					'client_secret'=>['VARCHAR'=>255],
					'user_info_field'=>['VARCHAR'=>255],
			];
		}

		static function users(){
			return [
					'service_id'=>['VARCHAR'=>255,'NOT NULL','KEY'=>'PRIMARY'],
					'user_id'=>['INT','NOT NULL']
			];
		}
	}


	class Message extends UmbrellaObjectWithId{
		const DELIVER_INSTANTLY = '0';
		const SEND_AT_8  = 1;
		const SEND_AT_10 = 2;
		const SEND_AT_12 = 4;
		const SEND_AT_14 = 8;
		const SEND_AT_16 = 16;
		const SEND_AT_18 = 32;
		const SEND_AT_20 = 64;
		const SENT = 'SENT';
		const WAITING = 'WAITING';

		static function table(){
			return [
				'id' => ['INTEGER','KEY'=>'PRIMARY'],
				'author' => ['INT','NOT NULL'],
				'timestamp' => ['INT','NOT NULL'],
				'subject' => ['TEXT'],
				'body' => ['TEXT'],
			];
		}

		static function recipients(){
			return [
				'message_id' => ['INT'],
				'user_id' => ['INT','NOT NULL'],
				'state' => ['INT','NOT NULL','DEFAULT'=>0],
				'PRIMARY KEY'=>['message_id','user_id']
			];
		}

		static function delivery(){
			$messages = Message::load(['state'=>Message::WAITING,'order'=>'user_id ASC']);
			debug($messages);
		}

		function assginReciever($user_id,$state = Message::WAITING){
			$sql = 'INSERT INTO recipients (message_id, user_id, state) VALUES (:mid, :uid, :state )';
			$args = [':mid'=>$this->id,':uid'=>$user_id,':state'=>$state];
			$db = get_or_create_db();
			$query = $db->prepare($sql);
			if (!$query->execute($args)) error("Was not able to assign message to reciever!");
		}

		function assignTo(array $recievers){
			global $user;
			if (!$this->save()) error("Was not able to save new message!");
			if (no_error()){
				foreach ($recievers as $reciever) $this->assginReciever($reciever->id,Message::WAITING);
			}
		}

		function load($options){
			global $user;

			$sql = 'SELECT * FROM recipients LEFT JOIN messages ON messages.id = recipients.message_id';

			if (!empty($options['user_id'])){
				$where = [ 'user_id = :uid' ];
				$args = [':uid' => $options['user_id']];
			}

			if (!empty($options['since'])){
				$where[] = 'timestamp > :since';
				$args[':since'] = $options['since'];
			}

			if (!empty($options['state'])){
				$where[] = 'state = :state';
				$args[':state'] = $options['state'];
			}

			if (!empty($where)) $sql .= ' WHERE ( '.implode(' ) AND ( ', $where).' )';

			$order = '';
			if (!empty($options['order'])){
				$order .= $options['order'].', ';
			}

			$sql .= ' ORDER BY '.$order.'id DESC';

			if (!empty($options['limit'])){
				$sql .= ' LIMIT :limit';
				$args[':limit'] = $options['limit'];
			}


			$db = get_or_create_db();
			$query = $db->prepare($sql);
			//debug(query_insert($query, $args));
			if (!$query->execute($args)) error('Was not able to load messages!');


			$rows = $query->fetchAll(PDO::FETCH_ASSOC);

			$users = [];

			$messages = [];
			foreach ($rows  as $id => $row){
				$message = new Message();
				$message->patch(['id'=>$id])->patch($row);
				$author_id = $message->author;
				if (empty($users[$author_id])) $users[$author_id] = User::load(['ids'=>$author_id]);
				$message->patch(['from'=>$users[$author_id]]);
				unset($message->dirty);
				$messages[$id] = $message;
			}
			return $messages;
		}

		function save(){
			$db = get_or_create_db();
			$known_fields = array_keys(Message::table());

			$fields = [];
			$args = [];
			foreach ($known_fields as $f){
				if (isset($this->{$f})){
					$fields[]=$f;
					$args[':'.$f] = $this->{$f};
				}
			}
			$query = $db->prepare('INSERT INTO messages ( '.implode(', ',$fields).' ) VALUES ( :'.implode(', :',$fields).' )');
			debug(query_insert($query, $args));
			assert($query->execute($args),'Was not able to insert new message');

			$this->id = $db->lastInsertId();

			return true;
		}


	}

	class Settings {
		static function table(){
			return [
					'key'	=> ['VARCHAR'=>255,'KEY'=>'PRIMARY'],
					'value'	=> ['VARCHAR'=>255,'NOT NULL'],
			];
		}
	}

	class Token extends UmbrellaObjectWithId{
		function destroy(){
			$db = get_or_create_db();
			$query = $db->prepare('DELETE FROM tokens WHERE token = :token');
			assert($query->execute([':token'=>$this->token]),'Was not able to execute DELETE statement.');
		}

		static function expired(){
			$db = get_or_create_db();
			$sql = 'SELECT '.implode(', ', array_keys(Token::table())).' FROM tokens WHERE expiration < :time';
			$query = $db->prepare($sql);
			if (!$query->execute([':time'=>time()])) throw new Exception('Was not able to request expired tokens.');
			$rows = $query->fetchAll(INDEX_FETCH);

			$tokens = [];
			foreach ($rows as $id => $row){
				$token = new Token();
				$token->patch($row);
				$token->token = $id;
				unset($token->dirty);
				$tokens[$id] = $token;

			}
			return $tokens;
		}

		static function  drop_expired(){
			$expired_tokens = Token::expired();
			foreach ($expired_tokens as $token) $token->revoke()->destroy();
		}

		static function getOrCreate($user = null){
			assert(!empty($user->id),'Parameter "user" null or empty!');
			$db = get_or_create_db();
			$query = $db->prepare('SELECT * FROM tokens WHERE user_id = :userid');
			assert($query->execute([':userid'=>$user->id]),'Was not able to execute SELECT statement.');
			$results = $query->fetchAll(PDO::FETCH_ASSOC);
			$token = null;
			foreach ($results as $row) $token = $row['token'];
			if ($token === null) $token = generateRandomString();
			$expiration = time()+3600; // now + one hour
			$query = $db->prepare('INSERT OR REPLACE INTO tokens (user_id, token, expiration) VALUES (:uid, :token, :expiration);');
			assert($query->execute([':uid'=>$user->id,':token'=>$token,':expiration'=>$expiration]),'Was not able to update token expiration date!');
			$_SESSION['token'] = $token;
			return $token;
		}

		static function load($key = null){
			Token::drop_expired();

			$sql = 'SELECT '.implode(', ', array_keys(Token::table())).' FROM tokens';
			$where = [];
			$args = [];

			if ($key != null){
				$where[] = 'token = :token';
				$args[':token'] = $key;
			}

			if (!empty($where)) $sql .= ' WHERE '.implode(' AND ', $where).' ';
			$db = get_or_create_db();
			$query = $db->prepare($sql);
			assert($query->execute($args),'Was not able to request token table.');
			$rows = $query->fetchAll(INDEX_FETCH);

			$tokens = [];
			foreach ($rows as $id => $row){
				$token = new Token();
				$token->patch($row);
				$token->token = $id;
				unset($token->dirty);
				if (!empty($key)) return $token;
				$tokens[$id] = $token;

			}
			if (!empty($key)) return null;
			return $tokens;
		}

		function revoke(){
			$db = get_or_create_db();
			$query = $db->prepare('SELECT domain FROM token_uses WHERE token = :token;');
			if ($query->execute([':token'=>$this->token])){
				$rows = $query->fetchAll(PDO::FETCH_ASSOC);
				foreach ($rows as $row) file_get_contents($row['domain'].'?revoke='.$this->token);
			}
			$query = $db->prepare('DELETE FROM token_uses WHERE token = :token');
			if (!$query->execute([':token'=>$this->token])) throw new Exception('Was not able to execute DELETE statement.');
			return $this;
		}

		static function table(){
			return [
					'token'=>['VARCHAR'=>255,'NOT NULL','KEY'=>'PRIMARY'],
					'user_id'=>['INTEGER','NOT NULL'],
					'expiration'=>['INT','NOT NULL']
			];
		}

		function useWith($domain){
			$db = get_or_create_db();

			// stretch expiration time
			$this->expiration = time()+300; // this value will be delivered to cliet apps
			$query = $db->prepare('UPDATE tokens SET expiration = :exp WHERE token = :token');
			$query->execute([':exp'=>($this->expiration+3000),':token'=>$this->token]); // the expiration period in the user app is way longer, so clients can revalidate from time to time

			if ($domain){
				$query = $db->prepare('INSERT OR IGNORE INTO token_uses (token, domain) VALUES (:token, :domain)');
				$query->execute([':token'=>$this->token,':domain'=>$domain]);
			}
			return $this;
		}

		function user(){
			$u = User::load(['ids'=>$this->user_id]);
			$u->token=$this;
			return $u;
		}

		static function uses(){
			return [
					'token'=>['VARCHAR'=>255],
					'domain'=>'TEXT',
					'PRIMARY KEY'=>['token','domain']
			];
		}
	}

	class User extends UmbrellaObjectWithId{
		function assigned_logins(){
			if (empty($this->assigned_logins)){
				global $user;
				$db = get_or_create_db();

				$sql = 'SELECT * FROM service_ids_users WHERE user_id = :id';
				$args = [':id'=>$user->id];
				$query = $db->prepare($sql);
				assert($query->execute($args),'Was not able to read list of assigned logins.');
				$rows = $query->fetchAll(INDEX_FETCH);
				$this->assigned_logins = array_keys($rows);
			}
			return $this->assigned_logins;
		}

		function correct($pass = null){
			if ($pass == null) return false;
			return ($this->pass == sha1($pass));
		}

		static function createAdmin(){
			$user = new User();
			$user->patch(['login'=>'admin','pass'=>'admin'])->save();
		}

		function exists(){
			$db = get_or_create_db();
			$query = $db->prepare('SELECT count(*) AS count FROM users WHERE login = :login');
			assert($query->execute([':login'=>$this->login]),'Was not able to check existance of user!');
			$results = $query->fetchAll(PDO::FETCH_ASSOC);
			if (empty($results)) return false;
			return $results[0]['count'] > 0;
		}

		function invite(){
			global $user;
			$db = get_or_create_db();
			$query = $db->prepare('DELETE FROM tokens WHERE user_id = :uid');
			$query->execute([':uid'=>$this->id]);
			$query = $db->prepare('INSERT INTO tokens (user_id, token, expiration) VALUES (:uid, :tok, :exp)');
			$token = generateRandomString();
			$args = [':uid'=>$this->id,':tok'=>$token,':exp'=>(time()+60*60*240)];
			assert($query->execute($args),'Was not able to set token for user.'); // token valid for 10 days
			$subject = t('◊ invited you to Umbrella',$user->login);
			$url = getUrl('user',$this->id.'/edit?token='.$token);
			$text = t('Umbrella is an online project management system developed by Stephan Richter.')."\n".
					t("Click the following link and set a password to join:\n◊",$url)."\n".
					t('Note: this link can only be used once!');
					t('This link is only valid for 10 days!');
			send_mail($user->email, $this->email, $subject, $text);
			info('Email has been sent to ◊',$this->email);
		}

		static function load($options = []){
			$db = get_or_create_db();

			$fields = User::table();
			if (empty($options['passwords']) || $options['passwords']!='load') unset($fields['pass']);
			$sql = 'SELECT * FROM users';
			$where = [];
			$args = [];

			$single = false;
			if (isset($options['ids'])){
				$ids = $options['ids'];
				if (!is_array($ids) && $ids = [$ids]) $single = true;
				$qMarks = str_repeat('?,', count($ids)-1).'?';
				$where[] = 'id IN ('.$qMarks.')';
				$args = array_merge($args, $ids);
			}

			if (!empty($options['login'])){
				$where[] = '(login = ? OR email = ?)';
				$args[] = $options['login'];
				$args[] = $options['login'];
			}

			if (!empty($where)) $sql .= ' WHERE '.implode(' AND ', $where);

			$query = $db->prepare($sql);

			assert($query->execute($args),'Was not able to load users!');
			$rows = $query->fetchAll(INDEX_FETCH);

			$users = [];
			$json_target = isset($options['target']) && $options['target']=='json';
			foreach ($rows as $id => $row){
				$user = new User();
				$user->patch($row);
				$user->id = $id;
				unset($user->dirty);
				if ($json_target) {
					unset($user->pass);
					unset($user->last_logoff);
				}
				if ($single) return $user;
				$users[$user->id] = $user;
			}
			if ($single) return null;
			return $users;
		}

		function lock(){
			$db = get_or_create_db();
			$query = $db->prepare('UPDATE users SET pass="" WHERE id = :id');
			assert($query->execute([':id'=>$this->id]));
			$query = $db->prepare('DELETE FROM service_ids_users WHERE user_id = :id');
			assert($query->execute([':id'=>$this->id]));
		}

		function login(){
			Token::getOrCreate($this);
			$redirect = param('returnTo');
			if (!$redirect && $this->id == 1) $redirect='index';
			if (!$redirect)	$redirect = getUrl('task');
			if (!$redirect)	$redirect = $this->id.'/view';
			redirect($redirect);
		}

		static function require_login(){
			global $theme;
			$url = getUrl('user','login?returnTo='.urlencode(location()));
			if (!isset($_SESSION['token']) || $_SESSION['token'] === null) redirect($url);

			$token = Token::load($_SESSION['token']);
			if ($token->user_id == null) redirect($url);
			if ($token != null) $user = User::load(['ids'=>$token->user_id]);
			if ($user == null) redirect($url);
			if (!empty($user->theme)) $theme = $user->theme;

			return $user;
		}

		function save(){
			if (!empty($this->id)) return $this->update();

			$db = get_or_create_db();

			if ($this->exists()) {
				error('User with this login name already existing!');
				return false;
			}

			$this->pass = sha1($this->pass); // TODO: better hashing
			$args = [];
			foreach (User::table() as $key => $definition){
				if (!isset($this->{$key})) continue;
				$args[$key] = $this->{$key};
			}

			$sql = 'INSERT INTO users ('.implode(', ', array_keys($args)).') VALUES (:'.implode(', :',array_keys($args)).' )';
			$query = $db->prepare($sql);
			assert ($query->execute($args),'Was not able to add user '.$this->login);
			info('User ◊ has been added',$this->login);
			return true;
		}

		static function table(){
			return [
					'id' => ['INTEGER','KEY'=>'PRIMARY'],
					'login' => ['VARCHAR'=>255,'NOT NULL'],
					'pass' => ['VARCHAR'=>255, 'NOT NULL'],
					'email' => ['VARCHAR'=>255],
					'theme'=> ['VARCHAR'=>50],
					'message_delivery'=>['VARCHAR'=>100,'DEFAULT'=>Message::DELIVER_INSTANTLY],
					'last_logoff'=>['INT','DEFAULT'=>'NULL']
			];
		}

		function update(){
			if (!empty($this->new_pass)) $this->patch(['pass'=>sha1($this->new_pass)]);
			if (in_array('login', $this->dirty) && User::exists($this->login)){
				error('User with this login name already existing!');
				return $this;
			}

			$db = get_or_create_db();
			$sql = 'UPDATE users SET ';
			$args = [];
			foreach (array_keys(User::table()) as $key){
				if ($key == 'id' || !in_array($key, $this->dirty)) continue;
				$args[':'.$key] = $this->{$key};
				$sql .= $key.' = :'.$key.', ';
			}
			if (empty($args)) {
				info('Nothing changed in your account!');
				return $this;
			}

			$sql=rtrim($sql,', ').' WHERE id = :id';
			$args[':id'] = $this->id;
			$query = $db->prepare($sql);
			assert ($query->execute($args),'Was not able to update user '.$this->login);
			info('User data has been updated.');
			warn('If you changed your theme, you will have to log off an in again.');
			return $this;
		}
	}
?>
