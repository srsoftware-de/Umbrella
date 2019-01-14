<?php include 'controller.php';

$service_name = param('service');
if ($service_name) $_SESSION['login_service_name'] = $service_name;
$login_services = LoginService::load();
$login_service = $_SESSION['login_service_name'] ? $login_services[$_SESSION['login_service_name']] : null;

if (isset($_SESSION['redirect'])){
	$_POST['returnTo'] = $_SESSION['redirect'];
	unset($_SESSION['redirect']);
} elseif ($redirect = param('returnTo')){
	$_SESSION['redirect'] = $redirect;
}

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php';
include 'lib/OpenIDConnectClient.php';

if ($login_service){
	$oidc = new OpenIDConnectClient($login_service->url,$login_service->client_id,$login_service->client_secret);

	if ($redirect = param('returnTo'))$_SESSION['redirect'] = $redirect;

	if ($oidc->authenticate()){
		$info = $oidc->requestUserInfo();
		$id = $_SESSION['login_service_name'].':'.$info->{$login_service->user_info_field};
		unset($_SESSION['login_service_name']);
		if ($user_id = reset(get_assigned_logins($id))) {
			User::load(['ids'=>$user_id])->login();
		} else {
			error('Your login provider successfully authenticated you, but the account there is not linked to any umbrella account!');
			redirect('login');
		}
	}
} else redirect(getUrl('user','login'));

include '../common_templates/closure.php'; ?>
