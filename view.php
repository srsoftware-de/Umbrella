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

if (isset($services['bookmark'])) $bookmark = request('bookmark','json_get?id='.sha1(location('*')));

$usrl = getUrl('user');

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>

<table class="wiki_page">
	<tr>
		<th><?= t('Page')?></th>
		<td>
			<span class="right symbol">
			<?php if ($page->permissions & Page::WRITE) { ?>
				<a href="edit" title="<?= t('edit')?>"></a>
				<a href="share"></a>
			<?php } ?>
				<a href="<?= $wiki.'/add_page'?>" title="<?= t('Add page')?>"></a>
			</span>
			<h1><?= $page->id ?></h1>
		</td>
	</tr>
	<tr>
		<th><?= t('Users')?></th>
		<td>
		<?php foreach ($page->users() as $user) { ?>
			<a class="button" href="<?= $usrl.$user['id'].'/view'?>"><?= $user['login']?></a>
		<?php } ?>
		</td>
	</tr>
	<tr>
		<th><?= t('Version ◊',$page->version)?></th>
		<td><?= markdown($page->content)?></td>
	</tr>
	<?php if ($bookmark && !empty($bookmark['tags'])) { ?>
	<tr>
		<th><?= t('Tags')?></th>
		<td>
		<?php $base_url = getUrl('bookmark');
		foreach ($bookmark['tags'] as $tag){ ?>
		<a class="button" href="<?= $base_url.$tag.'/view' ?>"><?= $tag ?></a>
		<?php } ?>
		</td>
	</tr>
	<?php } ?>
</table>

<?php if (isset($services['notes'])) echo request('notes','html',['uri'=>'wiki:'.$id],false,NO_CONVERSION); ?>

<?php include '../common_templates/closure.php'; ?>