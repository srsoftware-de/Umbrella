<?php $title = 'Umbrella Task Management';

include '../bootstrap.php';
include 'controller.php';

require_login('bookmark');
$base_url = getUrl('bookmark');
$tag = param('id');
if (!$tag) error('No tag passed to view!');

$tag = load_tag($tag);

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<fieldset class="tags">
	<legend><?= t('Tag "?"',$tag->tag) ?></legend>
	<?php foreach ($tag->links as $hash => $link ) {?>
	<fieldset>
		<legend><a class="symbol" href="../<?= $hash ?>/edit"></a><a class="symbol" href="../<?= $hash ?>/delete"></a> <?= isset($link['comment']) ? $link['comment']:$link['url']?></legend>
		<a target="_blank" href="<?= $link['url'] ?>" ><?= $link['url'] ?></a><br/>
		<?php if (isset($link['related'])) { foreach ($link['related'] as $related){ ?>
			<a class="button" href="../<?= $related ?>/view"><?= $related ?></a>
		<?php } } ?>
	</fieldset>
	<?php } ?>	
</fieldset>

<?php include '../common_templates/closure.php'; ?>
