<?php include 'controller.php';

require_login('bookmark');

if ($share_user = param('share_user_id')) {
	$bookmark = Bookmark::load(['url_hash'=>param('share_url_hash')]);
	$bookmark->share($share_user,param('notify'));
}



$id = param('id');
if (!$id) error('No tag passed to view!');

$tag = Tag::load(['tag'=>$id]);
$users = load_connected_users();

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<fieldset class="bookmark">
	<legend><?= t('Tag "◊"',$tag->tag) ?> - <a href="<?= getUrl('user','search?key='.$tag->tag)?>"><?= t('Search for "◊"',$tag->tag)?></a></legend>

	<?php foreach ($tag->bookmarks() as $hash => $bookmark ) {?>
	<fieldset>
		<legend>
			<a class="symbol" href="../<?= $hash ?>/edit?returnTo=<?= urlencode(location('*'))?>"></a>
			<a class="symbol" href="../<?= $hash ?>/delete?returnTo=<?= urlencode(location('*'))?>"></a>
			<a <?= $bookmark->external?'target="_blank"':''?> href="<?= $bookmark->url ?>" ><?= $bookmark->comment() ? $bookmark->comment()->comment:$bookmark->url ?></a>
		</legend>
		<a <?= $bookmark->external?'target="_blank"':''?> href="<?= $bookmark->url ?>" ><?= $bookmark->url ?></a>
		<div class="tags">
			<?php foreach ($bookmark->tags() as $tag){ ?>
			<a class="button" href="../<?= $tag->tag ?>/view"><?= $tag->tag ?></a>
			<?php } ?>
		</div>
		<fieldset class="share">
			<legend><?= t('share')?></legend>
			<form method="POST">
				<input type="hidden" name="share_url_hash" value="<?= $hash?>" />
				<input type="hidden" name="notify" value="1" />
				<select name="share_user_id">
				<option value=""><?= t('select user')?></option>
				<?php foreach ($users as $uid => $some_user) {
					if ($uid == $user->id) continue; ?>
				<option value="<?= $uid?>"><?= $some_user['login'] ?></option>
				<?php } ?>
				</select>
				<input type="submit" />
			</form>
		</fieldset>
	</fieldset>
	<?php } ?>
</fieldset>

<?php include '../common_templates/closure.php'; ?>
