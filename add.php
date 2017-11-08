<?php $title = 'Umbrella Project Management';

include '../bootstrap.php';
include 'controller.php';

require_login('bookmark');

$url = param('url');
if ($url) save_tag($url,param('tags'),param('comment'));

include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<form method="POST">
	<fieldset>
		<legend><?= t('Add new URL') ?></legend>
		<fieldset>
			<legend>URL</legend>
			<input type="text" name="url" />
		</fieldset>
		<fieldset>
			<legend>Tags</legend>
			<input type="text" name="tags" />
		</fieldset>
		<fieldset>
			<legend><?= t('Description')?></legend>
			<textarea name="comment"></textarea>
		</fieldset>
		
		<input type="submit" />
	</fieldset>
</form>

<?php include '../common_templates/closure.php'; ?>
