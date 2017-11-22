<?php $title = 'Umbrella Users';

include '../bootstrap.php';
include 'controller.php';

$service_name = param('service');
if ($service_name) $_SESSION['login_service_name'] = $service_name;
$login_services = get_login_services();
$login_service = $_SESSION['login_service_name'] ? $login_services[$_SESSION['login_service_name']] : null;


include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php';
include 'lib/OpenIDConnectClient.php';

if ($login_service){
	$oidc = new OpenIDConnectClient($login_service['url'],$login_service['client_id'],$login_service['client_secret']);
	if ($oidc->authenticate()){
		$info = $oidc->requestUserInfo();
		$id = $_SESSION['login_service_name'].':'.$info->{$login_service['user_info_field']};
		unset($_SESSION['login_service_name']);
		$user_id = reset(get_assigned_logins($id));
		perform_id_login($user_id);
	}
}

include '../common_templates/closure.php'; ?>