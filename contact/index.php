<?php $title = 'Umbrella Contacts';

include '../bootstrap.php';
include 'controller.php';

require_login('contact');
$contacts = read_contacts();
include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>
<div class="contacts">
<?php foreach ($contacts as $id => $contact){ ?>
	<fieldset>
		<legend><?= implode(', ',array_filter(explode(';', $contact['N']))) ?></legend>
		<span>
			<a class="symbol" title="download" href="<?= $id?>/download"></a>
			<a class="symbol" title="edit" href="<?= $id?>/edit"></a>
			<a class="symbol" title="assign with me" href="<?= $id?>/assign_with_me"></a>
		</span>
		<?php debug ($contact)?>
	</fieldset>
<?php } ?>
</div>

<?php include '../common_templates/closure.php'; ?>