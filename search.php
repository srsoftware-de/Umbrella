<?php include 'controller.php';

$user = User::require_login();

$key = param('key');
if (!$key){
	error('You need to specify a search key!');
	redirect(getUrl('user'));
}

$fulltext = param("fulltext",false) != false;
if (strpos($key,'+')!==false) $fulltext = true;
if (!isset($services['bookmark'])) $fulltext = true;

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include '../common_templates/messages.php'; ?>

<h2><?= t('Your search provided the following results:') ?></h2>

<?php if ($fulltext){
	foreach ($services as $service => $data){
		if ($service == 'user') continue;
		$result = request($service,'search',['key'=>$key],false,NO_CONVERSION);
		if ($result){ ?>
	<fieldset class="<?= $service ?>">
		<legend><?= t($data['name'])?></legend>
		<?= $result ?>
	</fieldset>
	<?php } // if result
	} // loop
} /* fulltext */ else /* tag search */ {
	$result = request('bookmark','search',['key'=>$key],false,NO_CONVERSION);
	if ($result){ ?>
	<fieldset>
		<legend><?= t('Bookmarks')?></legend>
		<?= $result ?>
	</fieldset>
	<?php } // if result
}

include '../common_templates/closure.php'; ?>
