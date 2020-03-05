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

$users = $page->users();
$writeable = false;
if (!empty($users[$user->id])) $writeable = $users[$user->id]['perms'] & Page::WRITE;

if (!$writeable){
	error('You are not allowed to edit this page!');
	redirect('view');
}

$new_title = param('new_title',$id);
$content = param('content',$page->content);

$redirect = null;
if ($content != $page->content) $redirect = $page->update($content);
if ($new_title != $page->id) $redirect = $page->rename($new_title);

if (isset($services['bookmark'])){
	$hash = sha1(str_replace('/edit', '/view', location('*')));
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
		<fieldset>
		<legend><?= t('Users')?></legend>
		<?php $guest = false; foreach ($page->users() as $uid => $user) {
			if (is_array($user)) { ?>
				<a class="button" href="<?= $usrl.$user['id'].'/view'?>"><?= $user['login']?></a>
			<?php } else if (!$guest){
				echo t('Guests');
				$guest = true;
			} // if user == 0
		} // foreach user ?>
		<p>
		<label>
			<input type="checkbox" name="notify" checked="checked" />
			<?= t('Notify users')?>
		</label>
		</p>
		</fieldset>
		<?php } ?>
		<button type="submit"><?= t('submit')?></button>
	</form>
</fieldset>

<?php include '../common_templates/closure.php'; ?>
