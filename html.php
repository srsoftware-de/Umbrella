<?php include 'controller.php';

require_login('bookmark');

$url_hash = param('hash');
$bookmark = Bookmark::load(['url_hash'=>$url_hash]);

if ($bookmark) { ?>
<fieldset>
	<legend><?= t('Tags')?></legend>
	<?php $base_url = getUrl('bookmark');
	if (!empty($bookmark->tags())){
	foreach ($bookmark->tags() as $tag => $dummy){ ?>
	<a class="button" href="<?= $base_url.'/'.$tag.'/view' ?>"><?= $tag ?></a>
	<?php }} ?>
</fieldset>
<?php }
