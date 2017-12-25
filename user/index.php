<?php $title = 'Umbrella Users';

include '../bootstrap.php';
include 'controller.php';

require_user_login();

$assigned_logins = get_assigned_logins();
$login_services = get_login_services();

if ($user_id = param('login')){
	if ($user->id == 1){
		perform_id_login($user_id);
	} else error('Only admin can switch users directly!');
}
if ($new_login_service  = param('login_service')){
	add_login_service($new_login_service);
}
if ($login_service_name = param('delete_login_service')){
	drop_login_service($login_service_name);
}
include '../common_templates/head.php';

include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php';

if (isset($services['contact'])){
	$contact = request('contact','json_assigned',null,false,true); 
?>

<fieldset>
	<legend>
		<?= isset($contact['FN'])?$contact['FN']:implode(' ',$contact['N'])?>
	</legend>
	<?php if (isset($contact['TEL'])) { ?>
	<fieldset>
		<legend><?= t('Phone numbers')?></legend>
		<table>
			<tr>
				<th><?= t('Type')?></th>
				<th><?= t('Number')?></th>
			</tr>
			<?php foreach ($contact['TEL'] as $key => $number) { ?>
			<tr>
				<th><?= $key?></th>
				<th><?= $number?></th>
			</tr>
			<?php } ?>
		</table>
	</fieldset>
	<?php } ?>
</fieldset>
<?php } ?>

<?php if (!empty($assigned_logins)) { ?>
<fieldset>
	<legend><?= t('assigned logins')?></legend>
	<table>
		<tr>
			<th><?= t('Service name')?></th>
			<th><?= t('User id')?></th>
			<th><?= t('Actions')?></th>
		</tr>
	<?php foreach ($assigned_logins as $login => $dummy) {
		$parts = explode(':', $login);
		$name = $parts[0];
		$id = $parts[1];
		if (!isset($login_services[$name])) continue;
		$login_service = $login_services[$name];
		?>
		<tr>
			<td><a href="<?= $login_service['url']?>"><?= $name ?></a></td>
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
			<th><?= t('Actions')?></th>
		</tr>
	<?php foreach (get_userlist() as $id => $u): ?>
		<tr>
			<td><?= $id ?></td>
			<td><?= $u['login'] ?></td>
			<td>
				<a class="symbol" title="<?= t('Edit user')?>" href="<?= $id?>/edit"></a>
				<a class="symbol" title="<?= t('Lock user account')?>" href="<?= $id?>/lock"> </a>
				<a class="symbol" title="<?= t('Login as ?',$u['login'])?>" href="?login=<?= $id?>"> </a>
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
		<?php foreach (login_services() as $name => $service) { ?>
		<tr>
			<td><?= $name ?></td>
			<td><a href="<?= $service['url']?>" target="_blank"><?= $service['url'] ?></td>
			<td><?= $service['client_id'] ?></td>
			<td><?= $service['client_secret'] ?></td>
			<td><?= $service['user_info_field'] ?></td>
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
<?php } // user id == 1

include '../common_templates/closure.php'; ?>
