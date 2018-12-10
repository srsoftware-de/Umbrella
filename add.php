<?php include 'controller.php';

require_login('bookmark');

$url = param('url');
$tags = param('tags');
if ($url && $tags) {
	$bookmark = Bookmark::add($url, $tags, param('comment'));
	if ($share_users = param('users')){
		foreach ($share_users as $u => $setting){
			switch ($setting){
				case SHARE_AND_NOTIFY:
					$bookmark->share($u,true);
					break;
				case SHARE_DONT_NOTIFY:
					$bookmark->share($u,false);
					break;
			}
		}
	}
	redirect(getUrl('bookmark')); // show last bookmarks
} else if ($url){
	error(t('Please set at least one tag!'));
} else if ($tags) {
	error(t('Please set url!'));
}

$users = load_connected_users();

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<form method="POST">
	<fieldset>
		<legend><?= t('Add new URL') ?></legend>
		<fieldset>
			<legend>URL</legend>
			<input type="text" name="url" id="url" value="<?= $url ?>" autofocus="true"/>
		</fieldset>
		<fieldset>
			<legend><?= t('Description')?></legend>
			<textarea name="comment" descr="<?= t('You can select a comment from the site here')?>"></textarea>
		</fieldset>
		<fieldset>
			<legend>Tags</legend>
			<input type="text" name="tags" value="<?= $tags ?>" />
		</fieldset>
		<fieldset>
			<legend><?= t('Share bookmark')?></legend>
			<table>
				<tr>
					<th><?= t('User')?></th>
					<th><?= t('Don\'t share')?></th>
					<th><?= t('Share & notify')?></th>
					<th><?= t('Share, don\'t notify')?></th>
				</tr>
				<?php foreach ($users as $usr) {  if ($usr['id']==$user->id) continue; ?>
				<tr>
					<td><?= $usr['login']?></td>
					<td><input type="radio" name="users[<?= $usr['id']?>]" value="<?= NO_SHARE ?>" checked="checked"/></td>
					<td><input type="radio" name="users[<?= $usr['id']?>]" value="<?= SHARE_AND_NOTIFY ?>" /></td>
					<td><input type="radio" name="users[<?= $usr['id']?>]" value="<?= SHARE_DONT_NOTIFY ?>" /></td>
				</tr>
				<?php } ?>
			</table>
		</fieldset>
		<input type="submit" />
	</fieldset>
	<script type="text/javascript">
	$('#url').bind('input',getHeadings_delayed);
	</script>
</form>

<?php include '../common_templates/closure.php'; ?>
