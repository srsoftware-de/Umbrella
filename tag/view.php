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

<fieldset>
	<legend>Tag "<?= $tag->tag ?>" <a class="symbol" href="edit"></a></legend>
	<ul>
		<?php foreach ($tag->urls as $url ) {?>
		<li><a target="_blank" href="<?= $url ?>"><?= $url ?></a></li>
		<?php } ?>
	</ul>
</fieldset>

<?php include '../common_templates/closure.php'; ?>
