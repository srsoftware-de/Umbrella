<?php include 'controller.php';

$user = User::require_login();

$domain = param('domain');
if (empty($domain)){
	error("Called ◊, but no domain given!","edit_foreign_service.php");
	redirect(getUrl('user','view'));
}

$foreign_service = ForeignService::load(['domain'=>$domain,'user_id'=>$user->id]);

if (empty($foreign_service->credentials)) $foreign_service->detectFields();

$newCredentials = param('field');
if (!empty($newCredentials)) {
	$foreign_service->patch(['credentials'=>$newCredentials])->save_credentials($user);
	redirect(getUrl('user','view/'.$user->id));
}

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php';

?>

<form method="POST">
	<fieldset>
		<input type="hidden" name="domain" value="<?= $domain ?>"/>
		<legend><?= t('Edit foreign login for ◊',$domain)?></legend>
		<table>
			<tr>
				<th><?= t('Key / Field')?></th>
				<th><?= t('Value')?></th>
			</tr>
			<?php foreach ($foreign_service->credentials as $name => $field) { ?>
			<tr>
				<th><?= t($name)?></th>
				<th>
					<input type="hidden" name="field[<?= $name ?>][key]" value="<?= $field['key'] ?>" />
					<input type="text" name="field[<?= $name ?>][val]" value="<?= $field['val'] ?>" />
				</th>
			</tr>
			<?php } ?>
		</table>
		<button type="submit"><?= t('Save')?></button>
	</fieldset>
</form>

<?php include '../common_templates/closure.php'; ?>