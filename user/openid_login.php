<?php $title = 'Umbrella Users';

include '../bootstrap.php';
include 'controller.php';

$service_name = param('service');
if ($service_name) $_SESSION['login_service_name'] = $service_name;
if ($_SESSION['login_service_name']) $login_service = get_login_services($_SESSION['login_service_name']);

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php';

include 'lib/OpenIDConnectClient.php';
debug($login_service);
debug($_SESSION);
$oidc = new OpenIDConnectClient($login_service['url'],$login_service['client_id'],$login_service['client_secret']);
$oidc->authenticate();
$info = $oidc->requestUserInfo();
debug($info);
$id = $_SESSION['login_service_name'].':'.$info->{$login_service['user_info_field']};
debug($id);

include '../common_templates/closure.php'; ?>