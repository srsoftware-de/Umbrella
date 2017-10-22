<?php $title = 'Umbrella Task Management';

include '../bootstrap.php';
include 'controller.php';

require_login('tag');
$tag = param('id');
if (!$tag) error('No tag passed to view!');

$tag = load_tag($tag);
if (isset($_POST['tag'])) update_tag($tag);

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<form method="POST">
<fieldset>
	<legend>Tag "<?= $tag->tag ?>"</legend>
	New tag name: <input type="text" name="tag" value="<?= $tag->tag ?>" />
	<ul>
		<?php foreach ($tag->urls as $url ) {?>
		<li><input type="text" name="urls[]" value="<?= $url ?>" /></li>
		<?php } ?>
	</ul>
	<input type="submit" />
</fieldset>
</form>
<?php include '../common_templates/closure.php'; ?>
