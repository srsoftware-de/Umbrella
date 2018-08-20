<?php

include '../bootstrap.php';
include 'controller.php';

require_login('bookmark');

if ($key = param('key')){
	$url_hashes = [];
	foreach (Comment::load(['search'=>$key]) as $comment) $url_hashes[] = $comment->url_hash;
	$bookmarks = array_merge(Bookmark::load(['url_hash'=>$url_hashes]),Bookmark::load(['search'=>$key]));
	$tags = Tag::load(['search'=>$key]);
	$url = getUrl('bookmark');
	if (!empty($tags)){ ?>
	<fieldset class="tags">
		<legend><?= t('Tags')?></legend>
	<?php foreach ($tags as $tag => $dummy){ ?>
	<a class="button" href="<?= $url.$tag.'/view' ?>"><?= $tag ?></a>
	<?php } ?>
	</fieldset> <?php } // not empty
	foreach ($bookmarks as $hash => $bookmark ) {?>
	<fieldset>
		<legend>
			<a class="symbol" href="<?= $url.$hash ?>/edit?returnTo=<?= urlencode(location('*'))?>"></a>
			<a class="symbol" href="<?= $url.$hash ?>/delete?returnTo=<?= urlencode(location('*'))?>"></a>
			<a <?= $link['external']?'target="_blank"':''?> href="<?= $bookmark->url ?>" ><?= $bookmark->comment() ? $bookmark->comment()->comment:$bookmark->url?></a>
		</legend>
		<a <?= $link['external']?'target="_blank"':''?> href="<?= $bookmark->url ?>" ><?= $bookmark->url ?></a>
		<?php if (!empty($bookmark->tags())) { ?>
		<div class="tags">		
			<?php foreach ($bookmark->tags() as $tag => $dummy){ ?>
			<a class="button" href="<?= getUrl('bookmark',$tag.'/view') ?>"><?= $tag ?></a>
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
<?php }