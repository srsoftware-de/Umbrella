<?php include 'controller.php';

assert(isset($_POST['token']),'No token set!');
$token = trim($_POST['token']);

assert($token != '','Token must not be empty!');
$domain = isset($_POST['domain']) ? $_POST['domain'] :  null;
$db = get_or_create_db();

$token = Token::load($token);
if (empty($token)) {
	http_response_code(400);
	die('invalid token');
}
$token->useWith($domain);

// the following lines fetch the user data from the users table
$user = $token->user();
echo json_encode($user);

