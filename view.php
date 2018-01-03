<?php $title = 'Umbrella Bookmark Management';

include '../bootstrap.php';
include 'controller.php';

require_login('bookmark');

if ($share_user = param('share_user_id')) share_bookmark($share_user,param('share_url_hash'));

$tag = param('id');
if (!$tag) error('No tag passed to view!');

$tag = load_tag($tag);
$users = load_connected_users();

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<fieldset class="bookmark">
	<legend><?= t('Tag "?"',$tag->tag) ?></legend>
	
	<?php foreach ($tag->links as $hash => $link ) {?>
	<fieldset>
		<legend>
			<a class="symbol" href="../<?= $hash ?>/edit"></a>
			<a class="symbol" href="../<?= $hash ?>/delete"></a>
			<a <?= $link['external']?'target="_blank"':''?> href="<?= $link['url'] ?>" ><?= isset($link['comment']) ? $link['comment']:$link['url']?></a>
		</legend>
		<a <?= $link['external']?'target="_blank"':''?> href="<?= $link['url'] ?>" ><?= $link['url'] ?></a>
		<?php if (isset($link['tags'])) { ?>
		<div class="tags">		
			<?php foreach ($link['tags'] as $related){ ?>
			<a class="button" href="../<?= $related ?>/view"><?= $related ?></a>
			<?php } ?>
		</div>
		<?php } ?>
		<fieldset class="share">
			<legend><?= t('share')?></legend>
			<form method="POST">
				<input type="hidden" name="share_url_hash" value="<?= $hash?>" />
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
