<?php $title = 'Umbrella login';

include 'controller.php';

$token = null;

assert(isset($_POST['token']),'No token set!');

$token = $_POST['token'];

$db = get_or_create_db();
$query = $db->prepare('SELECT * FROM tokens WHERE token = :token');
assert($query->execute(array(':token'=>$token)),'Was not able to check token');
$results = $query->fetchAll(PDO::FETCH_ASSOC);
assert(count($results>0),'Token not found');
$uid = $results[0]['user_id'];
$query = $db->prepare('SELECT * FROM users WHERE id = :uid');
assert($query->execute(array(':uid'=>$uid)),'Was not able to load user!');
$results = $query->fetchAll(PDO::FETCH_ASSOC);
assert(count($results>0),'User not found');
$user = $results[0];
unset($user['pass']);
echo json_encode($user);

