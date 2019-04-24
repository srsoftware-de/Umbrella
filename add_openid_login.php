<?php include 'controller.php';

User::require_login();

$login_services = LoginService::load();

if ($service_name = param('service')) $_SESSION['login_service_name'] = $service_name;
if (!empty($_SESSION['login_service_name'])) $login_service = $login_services[$_SESSION['login_service_name']];

if ($login_service){
	include 'lib/OpenIDConnectClient.php';

	$oidc = new OpenIDConnectClient($login_service->url,$login_service->client_id,$login_service->client_secret);
	try  {
		if ($oidc->authenticate()){
			$oidc->setRedirectURL(getUrl('user','add_openid_login'));
			$info = $oidc->requestUserInfo();
			unset($_SESSION['login_service_name']);
			$login_service->assign($info->{$login_service->user_info_field});
			redirect('index');
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
		<?php foreach ($login_services as $id => $data) {?>
		<a class="button" href="add_openid_login?service=<?= $id ?>"><?= t('Connect with ◊ account',$id) ?></a><br/>
		<?php }?>
	</fieldset>
<?php }

include '../common_templates/closure.php'; ?>