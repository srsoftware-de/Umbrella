<?php $title = 'Umbrella Task Management';

include '../bootstrap.php';
include 'controller.php';

require_login('tag');
$url_hash = param('id');
if (!$url_hash) error('No url hash passed to view!');

$link = load_url($url_hash);
if (isset($_POST['url'])) update_url($link);

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<form method="POST">
<fieldset>
	<legend>URL "<?= $link['url'] ?>"</legend>
	<label>
		New Url:
		<input type="text" name="url" value="<?= $link['url'] ?>" />
	</label><br/>
	<label>
		Description:
		<textarea name="comment"><?= $link['comment'] ?></textarea>
	</label>	<br/>
	<label>
		Tags:
		<input type="text" name="tags" value="<?= implode(' ',$link['tags']) ?>" /><br/>
		</label>
	<input type="submit" />
</fieldset>
</form>
<?php include '../common_templates/closure.php'; ?>
