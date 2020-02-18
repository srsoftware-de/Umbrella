<?php include 'controller.php';

$user = User::require_login();

$login_services = LoginService::load();

if ($user_id = param('login')){
	if ($user->id == 1){
		User::load(['ids'=>$user_id])->login();
	} else error('Only admin can switch users directly!');
}
if ($service_data  = param('login_service')){
	$service = new LoginService();
	$service->patch($service_data)->save();
}
if ($login_service_name = param('delete_login_service')) LoginService::load($login_service_name)->delete();
$limit = param('limit');
$options = ['since'=>$user->last_logoff];
if (!empty($limit)) $options = ['limit'=>$limit];
$options['user_id'] = $user->id;
$messages = Message::load($options);
$users = User::load();

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>
<fieldset>
	<legend>
	<?= $user->login ?>
	<span>
		<?php if (isset($user->id)) { ?>
		<a class="symbol" title="<?= t('edit your account')?>" href="<?= $base . $user->id ?>/edit"></a>
		<?php } ?>
		<a class="symbol" title="<?= t('add user')?>" href="<?= $base ?>add"></a>
		<a class="symbol" title="<?= t('connect with other account')?>" href="<?= $base ?>add_openid_login"></a>
	</span>
</legend>
<fieldset>
	<legend>
		<?= empty($limit)?t('Messages since last logoff'):t('Last ◊ messages',$limit)?>
	</legend>
	<table class="messages">
		<tr>
			<th><?= t('Created')?></th>
			<th><?= t('From')?></th>
			<th><?= t('Subject')?></th>
			<th><?= t('Text')?></th>
			<th><?= t('State')?></th>
		</tr>
		<?php foreach ($messages as $message){ ?>
		<tr>
			<td><?= date('Y-m-d / H:i:s',$message->timestamp) ?></td>
			<td><?= $users[$message->author]->login ?></td>
			<td><?= $message->subject ?></td>
			<td><?= markdown($message->body) ?></td>
			<td>
				<?php switch ($message->state){
					case Message::SENT: echo t('Already sent via mail.'); break;
					case Message::WAITING: echo t('Waiting to be sent by mail.'); break;
					default: echo t('Unknown message state');
				}
				?>
			</td>
		</tr>
		<?php } ?>
	</table>
</fieldset>
<?php

if (isset($services['contact'])){
	$contact = request('contact','json',['assgined'=>true]);
	if ($contact){
		if (isset($contact['TEL']['key'])) $contact['TEL'] = [$contact['TEL']];
?>
<fieldset>
	<legend>
		<?= isset($contact['FN'])?$contact['FN']:$contact['N']['given'].' '.$contact['N']['family']?>
	</legend>
	<?php if (isset($contact['TEL'])) { ?>
	<fieldset>
		<legend><?= t('Phone numbers')?></legend>
		<table>
			<tr>
				<th><?= t('Type')?></th>
				<th><?= t('Number')?></th>
			</tr>
			<?php foreach ($contact['TEL'] as $number) { ?>
			<tr>
				<th>
				<?php if (is_array($number['param']['TYPE'])) {
					foreach ($number['param']['TYPE'] as $type) echo t($type).' ';
				} else echo t($number['param']['TYPE']) ?>
				</th>
				<th><?= $number['val']?></th>
			</tr>
			<?php } ?>
		</table>
	</fieldset>
	<?php } ?>
</fieldset>
<?php }
} ?>

<?php if (!empty($user->assigned_logins())) { ?>
<fieldset>
	<legend><?= t('assigned logins')?></legend>
	<table>
		<tr>
			<th><?= t('Service name')?></th>
			<th><?= t('User id')?></th>
			<th><?= t('Actions')?></th>
		</tr>
	<?php foreach ($user->assigned_logins() as $login) {
		$parts = explode(':', $login);
		$name = $parts[0];
		$id = $parts[1];
		if (empty($login_services[$name])) continue;
		$login_service = $login_services[$name];
		?>
		<tr>
			<td><a href="<?= $login_service->url ?>"><?= $name ?></a></td>
			<td><?= $id ?></td>
			<td><a class="symbol" title="<?= t('Delete assignment')?>" href="<?= urlencode($login) ?>/deassign"> </a></td>
		</tr>
	<?php } ?>
	</table>
</fieldset>
<?php }?>

<?php if ($user->id == 1){  ?>
<fieldset class="userlist">
	<legend><?= t('List of users') ?></legend>
	<table>
		<tr>
			<th><?= t('Id')?></th>
			<th><?= t('Username')?></th>
			<th><?= t('Email')?></th>
			<th><?= t('Actions')?></th>
		</tr>
	<?php foreach (User::load() as $id => $u): ?>
		<tr>
			<td><?= $id ?></td>
			<td><?= $u->login ?></td>
			<td><?= $u->email ?></td>
			<td class="symbol">
				<a title="<?= t('Edit user')?>" href="<?= $id?>/edit"></a>
				<a title="<?= t('Lock user account')?>" href="<?= $id?>/lock"> </a>
				<a title="<?= t('Login as ◊',$u->login)?>" href="?login=<?= $id ?>"> </a>
				<?php if ($u->email!='') { ?><a title="<?= t('Send invitation email to ◊',$u->email)?>" href="<?= $id ?>/invite"> </a><?php } ?>
			</td>
		</tr>
	<?php endforeach; ?>
	</table>
</fieldset>


<fieldset class="login_service_list">
	<legend><?= t('List of login services')?></legend>
	<form method="POST">
	<table>
		<tr>
			<td><?= t('Name')?></td>
			<td><?= t('Auth Url')?></td>
			<td><?= t('Client Appliance ID')?></td>
			<td><?= t('Client Appliance secret')?></td>
			<td><?= t('User id field in response')?></td>
			<td><?= t('Actions') ?></td>
		</tr>
		<?php foreach (LoginService::load() as $name => $service) { ?>
		<tr>
			<td><?= $name ?></td>
			<td><a href="<?= $service->url ?>" target="_blank"><?= $service->url ?></td>
			<td><?= $service->client_id ?></td>
			<td><?= $service->client_secret ?></td>
			<td><?= $service->user_info_field ?></td>
			<td><a class="symbol" href="?delete_login_service=<?= urlencode($name )?>"></a></td>
		</tr>
		<?php } ?>
		<tr>
			<td><input type="text" name="login_service[name]" /></td>
			<td><input type="text" name="login_service[url]" /></td>
			<td><input type="text" name="login_service[client_id]" /></td>
			<td><input type="text" name="login_service[client_secret]" /></td>
			<td><input type="text" name="login_service[user_info_field]"/></td>
			<td><input type="submit" /></td>
		</tr>
	</table>
	</form>
</fieldset>
<?php } // user id == 1 ?>

</fieldset>

<?php include '../common_templates/closure.php'; ?>
