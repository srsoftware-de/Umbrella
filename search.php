<?php include 'controller.php';

require_login('wiki');



if ($key = param('key')){

	$pages = Page::load(['key'=>$key]);

	if (!empty($pages)){
		$wiki = getUrl('wiki');
		foreach ($pages as $page){ ?>
			<a class="button" href="<?= $wiki.$page->id.'/view'?>"><?= $page->id ?></a>
		<?php }
	}
}