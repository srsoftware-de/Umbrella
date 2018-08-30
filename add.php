<?php include 'controller.php';

require_login('bookmark');

$url = param('url');
$tags = param('tags');
if ($url && $tags) {
	Bookmark::add($url, $tags, param('comment'));
	redirect(getUrl('bookmark')); // show last bookmarks
} else if ($url){
	error(t('Please set at least one tag!'));
} else if ($tags) {
	error(t('Please set url!'));
}

include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<form method="POST">
	<fieldset>
		<legend><?= t('Add new URL') ?></legend>
		<fieldset>
			<legend>URL</legend>
			<input type="text" name="url" id="url" value="<?= $url ?>" autofocus="true"/>
		</fieldset>
		<fieldset>
			<legend><?= t('Description')?></legend>
			<textarea name="comment" descr="<?= t('You can select a comment from the site here')?>"></textarea>
		</fieldset>
		<fieldset>
			<legend>Tags</legend>
			<input type="text" name="tags" value="<?= $tags ?>" />
		</fieldset>
		
		<input type="submit" />
	</fieldset>
	<script type="text/javascript">
	$('#url').bind('input',getHeadings_delayed);
	</script>
</form>

<?php include '../common_templates/closure.php'; ?>
