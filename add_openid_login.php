<?php $title = 'Umbrella Users';

include '../bootstrap.php';
include 'controller.php';

require_user_login();

$service_name = param('service');
if ($service_name) $_SESSION['login_service_name'] = $service_name;
$login_services = get_login_services();
$login_service = isset($_SESSION['login_service_name']) ? $login_services[$_SESSION['login_service_name']] : null;

include 'lib/OpenIDConnectClient.php';

if ($login_service){
	$oidc = new OpenIDConnectClient($login_service['url'],$login_service['client_id'],$login_service['client_secret']);
	try  {
		if ($test = $oidc->authenticate()){
			$oidc->setRedirectURL(getUrl('user','add_openid_login'));
			$info = $oidc->requestUserInfo();
			$id = $_SESSION['login_service_name'].':'.$info->{$login_service['user_info_field']};
			unset($_SESSION['login_service_name']);
			assign_user_service($id);
		} 
	} catch (OpenIDConnectClientException $e){
		error($e->getMessage());
	}
	unset($_SESSION['login_service_name']);
}

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php';

if (!empty($login_services)) { ?>
	<fieldset>
		<legend><?= t('Login using OAuth 2 / OpenID Connect')?></legend>
		<?php foreach ($login_services as $name => $data) {?>
		<a class="button" href="add_openid_login?service=<?= $name ?>"><?= t('Connect with ? account',$name) ?></a><br/>
		<?php }?>
	</fieldset>
<?php }

include '../common_templates/closure.php'; ?>