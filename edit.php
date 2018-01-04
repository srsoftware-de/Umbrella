<?php $title = 'Umbrella Bookmark Management';

include '../bootstrap.php';
include 'controller.php';

require_login('bookmark');
if ($url_hash = param('id')){
	$link = load_url($url_hash);
	if (param('url')) {
		$tag = update_url($link);
		if ($redirect = param('returnTo')){
			redirect($redirect);
		} else redirect(getUrl('bookmark',$tag.'/view'));
	}
} else error('No url hash passed to view!');

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; 

if ($url_hash){ ?>

<form method="POST">
<fieldset>
	<legend><?= t('Edit URL ?','"'.$link['url'].'"'); ?></legend>
	<label>
		<?= t('New Url'); ?>
		<input type="text" name="url" value="<?= $link['url'] ?>" />
	</label><br/>
	<label>
		<?= t('Description'); ?>
		<textarea name="comment"><?= $link['comment'] ?></textarea>
	</label>	<br/>
	<label>
		<?= t('Tags'); ?>
		<input type="text" name="tags" value="<?= implode(' ',$link['tags']) ?>" /><br/>
		</label>
	<input type="submit" />
</fieldset>
</form>
<?php }

include '../common_templates/closure.php'; ?>
