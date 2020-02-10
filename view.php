<?php include 'controller.php';

// discover, if user is logged in
$user = empty($_SESSION['token']) ? null : getLocallyFromToken();
if ($user === null) validateToken('wiki');

$id = param('id');
$wiki = getUrl('wiki');
if (empty($id)){
	error('No id passed to view');
	redirect($wiki);
}

$filter = ['ids'=>$id];
if ($version = param('version')) $filter['version'] = $version;

$page = Page::load($filter);
if (empty($page)) {
	error('Page "◊" does not exist, but you can add it:',$id);
	redirect($wiki.'add_page?title='.$id);
}

$users = $page->users();
$readable =  false;
$writeable = false;
if (!empty($users[$user->id])){
	$readable  = $users[$user->id]['perms'] & Page::READ;
 	$writeable = $users[$user->id]['perms'] & Page::WRITE;
}
if (isset($users[0]) && $users[0]=Page::READ) $readable = true;

if (!$readable){
	error('You are not allowed to access this page!');
	redirect($wiki);
}


$title = $page->id . ' - '.$title;

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
			<?php if ($writeable) { ?>
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
		<?php $guest = false; foreach ($page->users() as $uid => $user) {
			if (is_array($user)) { ?>
				<a class="button" href="<?= $usrl.$user['id'].'/view'?>"><?= $user['login']?></a>
			<?php } else if (!$guest){
				echo t('Guests');
				$guest = true;
			} // if user == 0
		} // foreach user ?>
		</td>
	</tr>
	<tr>
		<th>
			<?php if ($version) { ?>
			<a href="view"><?= t('Latest&nbsp;version')?></a><br/>
			<?php } ?>
			<?= t('Version&nbsp;◊',$page->version)?>
			<?php $v = $page->version; while ($v > 1){ $v--; ?>
			<br/><a href="<?= $wiki.$id.'/view?version='.$v ?>"><?= t('Version&nbsp;◊',$v)?></a>
			<?php } ?>
		</th>
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
