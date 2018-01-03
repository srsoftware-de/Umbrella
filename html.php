<?php
include '../bootstrap.php';
include 'controller.php';

require_login('bookmark');

$url_hash = param('hash');
$bookmark = load_url($url_hash);

if ($bookmark) { ?>
<fieldset>
	<legend><?= t('Tags')?></legend>
	<?php $base_url = getUrl('bookmark');
	foreach ($bookmark['tags'] as $tag){ ?>
	<a class="button" href="<?= $base_url.'/'.$tag.'/view' ?>"><?= $tag ?></a>
	<?php } ?>
</fieldset>
<?php }
