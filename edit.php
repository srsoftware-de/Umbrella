<?php include 'controller.php';

require_login('bookmark');
if ($url_hash = param('id')){
	$bookmark = Bookmark::load(['url_hash'=>$url_hash]);
	if ($url = param('url')) {
		$bookmark->update(param('url'),param('tags_string'),param('comment'));

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
	<legend><?= t('Edit URL ◊','"'.$bookmark->url.'"'); ?></legend>
	<label>
		<?= t('New Url'); ?>
		<input type="text" name="url" value="<?= $bookmark->url ?>" />
	</label><br/>
	<label>
		<?= t('Description'); ?>
		<textarea name="comment"><?= $bookmark->comment()->comment ?></textarea>
	</label>	<br/>
	<label>
		<?= t('Tags'); ?>
		<input type="text" name="tags_string" value="<?= implode(' ',array_keys($bookmark->tags())) ?>" /><br/>
		</label>
	<input type="submit" />
</fieldset>
</form>
<?php }

include '../common_templates/closure.php'; ?>
