<?php $title = 'Umbrella Task Management';

include '../bootstrap.php';
include 'controller.php';

require_login('bookmark');
$url_hash = param('id');
if (!$url_hash) error('No url hash passed to view!');

$link = load_url($url_hash);
if (isset($_POST['confirm']) && $_POST['confirm']==true) {
	delete_link($link);
	redirect('..');
}

if (!$link['comment']) $link['comment'] = t('[uncommented link]');

include '../common_templates/head.php';
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<form method="POST">
<fieldset>
	<legend><?= t('Confirmation required') ?></legend>
	<?= t('Are you sure you want to delete "?" (?) ?',[$link['comment'],$link['url']]); ?><br/>
	<button type="submit" name="confirm" value="false">No</button><button type="submit" name="confirm" value="true">Yes</button>
</fieldset>
</form>
<?php include '../common_templates/closure.php'; ?>
