<?php include 'controller.php';

require_login('bookmark');

$base_url = getUrl('bookmark');

if ($key = param('key')){
	$url_hashes = [];
	foreach (Comment::load(['search'=>$key]) as $comment) $url_hashes[] = $comment->url_hash;
	$bookmarks = array_merge(Bookmark::load(['url_hash'=>$url_hashes,'order' => 'timestamp DESC']),Bookmark::load(['search'=>$key,'order' => 'timestamp DESC']));
	$tags = Tag::load(['search'=>$key]);
	$url = getUrl('bookmark');
	if (!empty($tags)){ ?>
	<fieldset class="tags">
		<legend><?= t('Tags')?></legend>
	<?php foreach ($tags as $tag => $dummy){ ?>
	<a class="button" href="<?= $url.$tag.'/view' ?>"><?= emphasize($tag,$key) ?></a>
	<?php } ?>
	</fieldset> <?php } // tags not empty
	$legend = t('external links');
	include 'list.php';
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
