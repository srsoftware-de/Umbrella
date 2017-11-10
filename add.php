<?php $title = 'Umbrella Project Management';

include '../bootstrap.php';
include 'controller.php';

require_login('bookmark');

$url = param('url');
$tags = param('tags');
if ($url && $tags) {
	save_tag($url,param('tags'),param('comment'));
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
			<input type="text" name="url" value="<?= $url ?>" />
		</fieldset>
		<fieldset>
			<legend><?= t('Description')?></legend>
			<textarea name="comment"></textarea>
		</fieldset>
		<fieldset>
			<legend>Tags</legend>
			<input type="text" name="tags" value="<?= $tags ?>" />
		</fieldset>
		
		<input type="submit" />
	</fieldset>
</form>

<?php include '../common_templates/closure.php'; ?>
