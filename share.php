<?php include 'controller.php';

require_login('wiki');

$id = param('id');
$wiki = getUrl('wiki');
if (empty($id)){
	error('No id passed to view');
	redirect($wiki);
}

$page = Page::load(['ids'=>$id]);
if (empty($page)) {
	error('Page "◊" does not exist, but you can add it:',$id);
	redirect($wiki.'add_page?title='.$id);
}

$users = $page->users();
$writeable = false;
if (!empty($users[$user->id])) $writeable = $users[$user->id]['perms'] & Page::WRITE;

if (!$writeable){
	error('You are not allowed to edit this page!');
	redirect('view');
}

$page->users();

$users = request('user','json');

$user_rights = post('user_rights');
if (!empty($user_rights)){
	$page->grant_access($user_rights);
	redirect(getUrl('wiki',$id.'/view'));
}

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>

<form method="POST">
<fieldset>
	<legend><?= t('Add user(s) to "◊"',$page->id)?></legend>
	<table>
		<tr>
			<th><?= t('None')?></th>
			<th><?= t('Read')?></th>
			<th><?= t('Read+Write')?></th>
			<th><?= t('Users')?></th>
		</tr>
		<tr>
			<td><input type="radio" name="user_rights[0]" value="0" <?= empty($page->users[0]) ? 'checked="checked" ':"" ?>/></td>
			<td><input type="radio" name="user_rights[0]" value="<?= Page::READ ?>"  <?= $page->users[0]['perms']==Page::READ ? 'checked="checked" ':"" ?>/></td>
			<td></td>
			<td><?= t('Guests') ?></td>
		</tr>
	<?php foreach($users as $user) { $id = $user['id']?>
		<tr>
			<td><input type="radio" name="user_rights[<?= $id ?>]" value="0" <?= empty($page->users[$id]) ? 'checked="checked" ':"" ?>/></td>
			<td><input type="radio" name="user_rights[<?= $id ?>]" value="<?= Page::READ ?>"  <?= $page->users[$id]['perms']==Page::READ ? 'checked="checked" ':"" ?>/></td>
			<td><input type="radio" name="user_rights[<?= $id ?>]" value="<?= Page::READ | Page::WRITE ?>" <?= $page->users[$id]['perms']==(Page::READ | Page::WRITE) ? 'checked="checked" ':"" ?> /></td>
			<td><?= $user['login']?></td>
		</tr>
	<?php } ?>
	</table>
	<button type="submit"><?= t('submit')?></button>
</fieldset>
</form>

<?php include '../common_templates/closure.php'; ?>