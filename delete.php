<?php include 'controller.php';

require_login('bookmark');
$url_hash = param('id');
if (!$url_hash) error('No url hash passed to view!');

$bookmark = Bookmark::load(['url_hash'=>$url_hash]);

if (isset($_POST['confirm']) && $_POST['confirm']==true) {
	$bookmark->delete();
	if ($redirect = param('returnTo')){
		redirect($redirect);
	} else redirect('..');
}

if (!$bookmark->comment()) $bookmark->comment = (new Comment())->patch(['comment'=>t('[uncommented link]')]);


include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<form method="POST">
<fieldset>
	<legend><?= t('Confirmation required') ?></legend>
	<?= t('Are you sure you want to delete "?" (?) ?',[$bookmark->comment()->comment,$bookmark->url]); ?><br/>
	<button type="submit" name="confirm" value="false"><?= t('No')?></button>
	<button type="submit" name="confirm" value="true"><?= t('Yes')?></button>
</fieldset>
</form>
<?php include '../common_templates/closure.php'; ?>
