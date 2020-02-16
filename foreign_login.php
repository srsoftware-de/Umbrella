<?php include 'controller.php';

$token = post('token');
assert($token != null,'No token set!');
assert($token != '','Token must not be empty!');

$domain = param('domain');
$db = get_or_create_db();

$token = Token::load($token);
if (empty($token)) {
	http_response_code(400);
	die('invalid token');
}
$token->useWith($domain);

$foreignService = ForeignService::load(['domain'=>$domain,'user_id'=>$token->user_id]);
if (empty($foreignService) || empty($foreignService->credentials)){
	$foreignService = new ForeignService();
	$foreignService->patch(['domain'=>$domain,'name'=>$domain])->save();
} else {
	echo $foreignService->json_credentials();
}

