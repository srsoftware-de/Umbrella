<?php include 'controller.php';

// discover, if user is logged in
$user = empty($_SESSION['token']) ? null : getLocallyFromToken();
if ($user === null) validateToken('wiki');

$wiki = getUrl('wiki');
$id = param('id');
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

if (!$writeable){
	error('You are not allowed to edit this page!');
	redirect($wiki);
}

$commit = param('commit',false);
if ($commit == 'true'){
	$page->delete_version();
	redirect($wiki.$page->id.'/view');
}

$title = $page->id . ' - '.$title;

if (isset($services['bookmark'])) $bookmark = request('bookmark',sha1(location('*')).'/json');

$usrl = getUrl('user');

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>

<fieldset>
	<legend><?= t('Delete „◊“, version ◊?',[$page->id,$page->version])?></legend>
	<?= t('Are you sure you want to delete the following content?')?>
	<a class="button" href="view?version=<?= $page->version ?>"><?= t('No')?></a>
	<a class="button" href="delete?version=<?= $page->version ?>&commit=true"><?= t('Yes')?></a>
	<h2><?= $page->id ?></h2>
</fieldset>
<?= markdown($page->content)?>
<?php include '../common_templates/closure.php'; ?>
