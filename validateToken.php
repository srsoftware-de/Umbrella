<?php $title = 'Umbrella login';

include '../bootstrap.php';
include 'controller.php';

assert(isset($_POST['token']),'No token set!');
$token = trim($_POST['token']);
assert($token != '','Token must not be empty!');
$domain = isset($_POST['domain']) ? $_POST['domain'] :  null;
$db = get_or_create_db();

$token = testValidityOf($token);
if ($token==null) {
	http_return_code(400);
	die('invalid token');
}

if ($domain){
	$query = $db->prepare('INSERT OR IGNORE INTO token_uses (token, domain) VALUES (:token, :domain)');
	$query->execute([':token'=>$token['token'],':domain'=>$domain]);
}

// the following lines fetch the user data from the users table
$query = $db->prepare('SELECT * FROM users WHERE id = :uid');
assert($query->execute([':uid'=>$token['user_id']]),'Was not able to load user!');
$results = $query->fetchAll(PDO::FETCH_ASSOC);

if (empty($results)) throw new \Exception('User not found');

$user = $results[0];
unset($user['pass']);
$user['token']=$token;
echo json_encode($user);

