<?php $title = 'Umbrella login';

include 'controller.php';

assert(isset($_POST['token']),'No token set!');
$token = $_POST['token'];
$db = get_or_create_db();

// the following lines fetch the user id from the token table
$query = $db->prepare('SELECT * FROM tokens WHERE token = :token AND expiration > :time');
assert($query->execute(array(':token'=>$token,':time'=>time())),'Was not able to check token');
$results = $query->fetchAll(PDO::FETCH_ASSOC);
assert(count($results>0),'Token not found');
$token = $results[0];
$token['expiration'] = time()+3600;
// stretch expiration time
$query = $db->prepare('UPDATE tokens SET expiration = :exp WHERE user_id = :uid');
$query->execute(array(':exp'=>$token['expiration'],':uid'=>$token['user_id']));

// the following lines fetch the user data from the users table
$query = $db->prepare('SELECT * FROM users WHERE id = :uid');
assert($query->execute(array(':uid'=>$token['user_id'])),'Was not able to load user!');
$results = $query->fetchAll(PDO::FETCH_ASSOC);
assert(count($results>0),'User not found');
$user = $results[0];
unset($user['pass']);
$user['token']=$token;
echo json_encode($user);

