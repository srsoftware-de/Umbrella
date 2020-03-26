<?php include 'controller.php';

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
	<a class="button" href="<?= $url.$tag.'/view' ?>"><?= emphasize($tag,$key) ?></a>
	<?php } ?>
	</fieldset> <?php } // tags not empty
	foreach ($bookmarks as $hash => $bookmark ) {?>
	<fieldset>
		<legend>
			<a class="symbol" href="<?= $url.$hash ?>/edit?returnTo=<?= urlencode(location('*'))?>"></a>
			<a class="symbol" href="<?= $url.$hash ?>/delete?returnTo=<?= urlencode(location('*'))?>"></a>
			<a <?= $bookmark->external?'target="_blank"':''?> href="<?= $bookmark->url ?>" ><?= emphasize($bookmark->comment() ? $bookmark->comment()->comment:$bookmark->url,$key)?></a>
		</legend>
		<a <?= $bookmark->external?'target="_blank"':''?> href="<?= $bookmark->url ?>" ><?= emphasize($bookmark->url,$key) ?></a>
		<?php if (!empty($bookmark->tags())) { ?>
		<div class="tags">
			<?php foreach ($bookmark->tags() as $tag => $dummy){ ?>
			<a class="button" href="<?= getUrl('bookmark',$tag.'/view') ?>"><?= emphasize($tag,$key) ?></a>
			<?php } ?>
		</div>
		<?php } ?>
	</fieldset>
	<?php }
} // key given

if ($key = param('tag')){
	$tags = Tag::load(['search'=>$key]);
	if (!empty($tags)){
		$url = getUrl('bookmark'); ?>
	<fieldset class="tags">
		<legend><?= t('Tags')?></legend>
	<?php foreach ($tags as $tag => $dummy){ ?>
	<a class="button" href="<?= $url.$tag.'/view' ?>"><?= emphasize($tag,$key) ?></a>
	<?php } ?>
	</fieldset> <?php } // tags not empty
}