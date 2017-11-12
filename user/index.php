<?php $title = 'Umbrella Users';

include '../bootstrap.php';
include 'controller.php';

require_user_login();

$assigned_logins = get_assigned_logins();
$login_services = get_login_services();
//if ($user->id != 1) error('Currently, only admin can view the user list!');

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
	<legend><?= t('Assigned logins')?></legend>
	<table>
		<tr>
			<th><?= t('Service Name')?></th>
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
			<td><a class="symbol" title="Delete assignment" href="<?= urlencode($login) ?>/deassign"> </a></td>
		</tr>
	<?php } ?>
	</table>
</fieldset>
<?php }?>

<?php if ($user->id == 1){ $users = get_userlist(); ?>

<table>
	<tr>
		<th><?= t('Id')?></th>
		<th><?= t('Username')?></th>
		<th><?= t('Actions')?></th>
	</tr>
<?php foreach ($users as $id => $u): ?>
	<tr>
		<td><?= $id ?></td>
		<td><?= $u['login'] ?></td>
		<td>
			<a class="symbol" title="<?= t('Edit user')?>" href="<?= $id?>/edit"></a>
			<a class="symbol" title="<?= t('Delete user')?>" href="<?= $id?>/delete"> </a>
		</td>
	</tr>
<?php endforeach; ?>

</table>
<?php } // user id == 1

include '../common_templates/closure.php'; ?>
