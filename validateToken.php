<?php include 'controller.php';

if (empty($_POST['token'])) throw new Exception('No token set!');
$token = trim($_POST['token']);

if (empty($token)) throw new Exception('Token must not be empty!');
$domain = isset($_POST['domain']) ? $_POST['domain'] :  null;

$token = Token::load($token);
if (empty($token)) {
	http_response_code(400);
	die('invalid token');
}
$token->useWith($domain);

// the following lines fetch the user data from the users table
$user = $token->user();
echo json_encode($user);

