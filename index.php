<?php include 'controller.php';

require_login('wiki');

$pages = Page::load(['user_id'=>$user->id]);
$wiki = getUrl('wiki');
include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>

<fieldset class="wiki_pages">
	<legend>
		<?= t('Pages') ?>
		<span class="symbol">
			<a href="add_page" title="<?= t('add page')?>"></a>
		</span>
	</legend>
	<?php foreach ($pages as $page){ ?>
		<a class="button" href="<?= $wiki.$page->id ?>/view"><?= $page->id ?></a><br/>
	<?php }?>
</fieldset>

<?php include '../common_templates/closure.php'; ?>
