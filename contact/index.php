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
<?php foreach ($contacts as $id => $contact){ ?>
	<fieldset>
		<legend>Contact <?= $id ?></legend>
		<span>
			<a href="<?= $id?>/download">Download</a>
			<a href="<?= getUrl('files','add_user_to?file='.$file_hash) ?>">Share</a>
			<a href="<?= $id?>/edit">Edit</a>
			<a href="<?= $id?>/assign_with_me">Assign with me</a>
		</span>
		<?php debug ($contact)?>
	</fieldset>
<?php } ?>
</div>

<?php include '../common_templates/closure.php'; ?>