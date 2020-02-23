<?php include 'controller.php';

require_login('wiki');

$pages = Page::load(['user_id'=>$user->id]);
$wiki = getUrl('wiki');

$letter = '0-9';
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
	<h3>0-9</h3>
	<?php foreach ($pages as $page){
		$first_letter = strtoupper(substr($page->id, 0,1));
		if (ctype_alpha($first_letter) && $letter != $first_letter){
			$letter = $first_letter;
			echo '<h3>'.$letter.'</h3>';
		} ?>

		<a class="button" href="<?= $wiki.$page->id ?>/view"><?= $page->id ?></a>
	<?php }?>
</fieldset>

<?php include '../common_templates/closure.php'; ?>
