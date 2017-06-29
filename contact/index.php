<?php $title = 'Umbrella Contacts';

include '../bootstrap.php';
include 'controller.php';

require_login();
$contacts = read_contacts();
include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>
<div class="contacts">
<?php foreach ($contacts as $file_hash => $get){ ?>
	<fieldset>
		<legend><?= $get['filename'] ?></legend>
		<span>
			<a href="<?= getUrl('files','download?file='.$file_hash) ?>">Download</a>
			<a href="<?= getUrl('files','add_user_to?file='.$file_hash) ?>">Share</a>
		</span>
		<?php debug (serialize_vcard($get['vcard']))?>
	</fieldset>
<?php } ?>
</div>

<?php include '../common_templates/closure.php'; ?>