<?php include 'controller.php';

require_login('wiki');

$id = param('id');
$wiki = getUrl('wiki');
if (empty($id)){
	error('No id passed to view');
	redirect($wiki);
}

$page = Page::load(['ids'=>$id]);
if (empty($page)) {
	error('Page "◊" does not exist, but you can add it:',$id);
	redirect($wiki.'add_page?title='.$id);
}

if (!($page->permissions & Page::WRITE)){
	error('You are not allowed to edit this page!');
	redirect('view');
}

$new_title = param('new_title',$id);
$content = param('content',$page->content);

$redirect = null;
if ($content != $page->content) $redirect = $page->update($content);
if ($new_title != $page->id) $redirect = $page->rename($new_title);

if (isset($services['bookmark'])){
	$hash = sha1($wiki.$id.'/view');
	$bookmark = request('bookmark','json_get?id='.$hash);
	$tags = param('tags');
	if (!empty($tags)) $page->setTags($tags);
}

if (!empty($redirect)) redirect($wiki.'/'.$redirect);

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>

<fieldset>
	<legend>
		<?= t('Edit page') ?>
	</legend>
	<form method="POST">
		<fieldset>
			<legend><?= t('Title')?></legend>
			<input type="text" name="new_title" value="<?= $new_title ?>" />
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