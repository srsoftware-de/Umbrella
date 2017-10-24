<?php $title = 'Umbrella Task Management';

include '../bootstrap.php';
include 'controller.php';

require_login('tag');
$tag = param('id');
if (!$tag) error('No tag passed to view!');

$tag = load_tag($tag);

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<fieldset class="tags">
	<legend>Tag "<?= $tag->tag ?>"</legend>
	<?php foreach ($tag->links as $hash => $link ) {?>
	<fieldset>
		<legend><?= $link['url'] ?> <a class="symbol" href="../<?= $hash ?>/edit"></a></legend>
		<span>
			<a target="_blank" href="<?= $link['url'] ?>" ><?= isset($link['comment']) ? $link['comment']."<br/>":'[No Description]' ?></a>
		</span>			
		<?php if (isset($link['related'])) { ?>
		<span>
			<?php foreach ($link['related'] as $related){ ?>
			<a class="button" href="../<?= $related ?>/view"><?= $related ?></a>
			<?php } ?>
		</span>
		<?php } ?>
	</fieldset>
	<?php } ?>	
</fieldset>

<?php include '../common_templates/closure.php'; ?>
