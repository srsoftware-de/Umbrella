<?php $title = 'Umbrella login';

include '../bootstrap.php';
include 'controller.php';

assert(isset($_POST['token']),'No token set!');
$token = trim($_POST['token']);
assert($token != '','Token must not be empty!');
$domain = isset($_POST['domain']) ? $_POST['domain'] :  null;  
$db = get_or_create_db();
// the following lines fetch the user id from the token table
$query = $db->prepare('SELECT * FROM tokens WHERE token = :token AND expiration > :time');
assert($query->execute(array(':token'=>$token,':time'=>time())),'Was not able to check token');
$results = $query->fetchAll(PDO::FETCH_ASSOC);

assert(!empty($results),'Token not found');

$token = $results[0];

// stretch expiration time
$token['expiration'] = time()+300; // this value will be delivered to cliet apps
$query = $db->prepare('UPDATE tokens SET expiration = :exp WHERE user_id = :uid');
$query->execute(array(':exp'=>($token['expiration']+3000),':uid'=>$token['user_id'])); // the expiration period in the user app is way longer, so clients can revalidate from time to time

if ($domain){
	$query = $db->prepare('INSERT OR IGNORE INTO token_uses (token, domain) VALUES (:token, :domain)');
	$query->execute([':token'=>$token['token'],':domain'=>$domain]);	
}

// the following lines fetch the user data from the users table
$query = $db->prepare('SELECT * FROM users WHERE id = :uid');
assert($query->execute(array(':uid'=>$token['user_id'])),'Was not able to load user!');
$results = $query->fetchAll(PDO::FETCH_ASSOC);

if (count($results)<1) throw new \Exception('User not found');

$user = $results[0];
unset($user['pass']);
$user['token']=$token;
echo json_encode($user);

