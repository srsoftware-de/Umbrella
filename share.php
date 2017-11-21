<?php $title = 'Umbrella Files';

include '../bootstrap.php';
include 'controller.php';

require_login('files');

$filename = param('file');


$user_id = param('user_id');
if ($user_id){
	share_file($filename,$user_id);
}

$unshare_user = param('unshare');
if ($unshare_user){
	unshare_file($filename,$unshare_user);
}

$shares = get_shares($filename);
$users = load_connected_users();


include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php';

?>

<fieldset>
	<legend>
		<?= t('File shares of file ?',$filename)?>
	</legend>
	<form method="POST">
	<fieldset>
		<legend><?= t('add user')?></legend>
		<select name="user_id">
		<option value=""><?= t('select user')?></option>
		<?php foreach ($users as $uid => $some_user) {
			if ($uid == $user->id) continue;
			?>
		<option value="<?= $uid?>"><?= $some_user['login'] ?></option>
		<?php } ?>
		</select>
		<input type="submit" />
	</fieldset>
	</form>
	<table>
		<tr>
			<th><?= t('User'); ?></th>
			<th><?= t('Actions') ?></th>
		</tr>
		<?php foreach ($shares as $share) { 
			$user_id = $share['user_id'];?>
			
		<tr>
			<td><?= $users[$user_id]['login']?></td>
			<td>
				<a class="symbol" title="<?= t('cancel sharing') ?>" href="<?= location() ?>&unshare=<?= $user_id ?>"></a>
			</td>
		</tr>
		<?php } ?>
	</table>
</fieldset>

<?php include '../common_templates/closure.php'; ?>
