<?php include 'controller.php';

require_login('wiki');

$title = param('title');
$content = param('content');

if (!empty($title) && !empty($content)){
	$page = new Page();
	try {
		$page->patch(['id'=>$title,'content'=>$content])->save();

		if (isset($services['bookmark'])){
			$hash = sha1($wiki.$title.'/view');
			$bookmark = request('bookmark',$hash.'/json');
			$tags = param('tags');
			if (!empty($tags)) $page->setTags($tags);
		}
		info(t('The page "◊" has been created',$title).' – '.t('<a href="share">Click here</a> to share it with other users.'));
	} catch (Exception $e) {
		error($e->getMessage());
	}
	redirect(getUrl('wiki',$page->id.'/view'));
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
			<legend><?= t('Description - <a target="_blank" href="◊">↗Markdown</a> and <a target="_blank" href="◊">↗PlantUML</a> supported',t('MARKDOWN_HELP'),t('PLANTUML_HELP'))?></legend>
			<textarea name="content" id="preview-source"><?= $content ?></textarea>
			<div id="preview" />
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
