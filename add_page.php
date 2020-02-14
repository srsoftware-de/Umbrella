<?php include 'controller.php';

require_login('wiki');

$title = param('title');
$content = param('content');

if (!empty($title) && !empty($content)){
	$page = new Page();
	$page->patch(['id'=>$title,'content'=>$content])->save();

	if (isset($services['bookmark'])){
		$hash = sha1($wiki.$title.'/view');
		$bookmark = request('bookmark','json_get?id='.$hash);
		$tags = param('tags');
		if (!empty($tags)) $page->setTags($tags);
	}

	redirect(getUrl('wiki',$page->id.'/share'));
}

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>

<fieldset>
	<legend>
		<?= t('Add page') ?>
	</legend>
	<form method="POST">
		<fieldset>
			<legend><?= t('Title')?></legend>
			<input type="text" name="title" value="<?= $title ?>" />
		</fieldset>
		<fieldset>
			<legend><?= t('Description - <a target="_blank" href="◊">Markdown supported ↗cheat sheet</a>',t('MARKDOWN_HELP'))?></legend>
			<textarea name="content"><?= $content ?></textarea>
		</fieldset>
		<?php if (isset($services['bookmark'])){ ?>
		<fieldset>
			<legend><?= t('Tags')?></legend>
			<input type="text" name="tags" value="<?= $bookmark ? htmlspecialchars(implode(' ', $bookmark['tags'])) : ''?>" />
		</fieldset>
		<?php } ?>
		<button type="submit"><?= t('submit')?></button>
	</form>
</fieldset>

<?php include '../common_templates/closure.php'; ?>