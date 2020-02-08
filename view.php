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

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>

<span class=" right symbol">
	<a href="edit" title="<?= t('edit')?>"></a>
	<a href="<?= $wiki.'/add_page'?>" title="<?= t('Add page')?>"></a>
</span>
<h1><?= $page->id ?></h1>

<fieldset>
	<legend><?= t('Version ◊',$page->version)?></legend>
	<?= markdown($page->content)?>
</fieldset>

<?php if ($bookmark && !empty($bookmark['tags'])) { ?>
<fieldset>
	<legend><?= t('Tags')?></legend>
	<?php $base_url = getUrl('bookmark');
	foreach ($bookmark['tags'] as $tag){ ?>
		<a class="button" href="<?= $base_url.$tag.'/view' ?>"><?= $tag ?></a>
	<?php } ?>
</fieldset>
<?php } ?>

<?php if (isset($services['notes'])) echo request('notes','html',['uri'=>'wiki:'.$id],false,NO_CONVERSION); ?>
<?php include '../common_templates/closure.php'; ?>