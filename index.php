<?php $title = 'Umbrella Contacts';

include '../bootstrap.php';
include 'controller.php';

require_login('contact');

$vcards = VCard::load();
include '../common_templates/head.php'; 
include '../common_templates/main_menu.php';
include 'menu.php';
include '../common_templates/messages.php'; ?>

<fieldset>
	<legend><?= t('Contacts') ?></legend>
	<table>
		<tr>
		</tr>
		<?php foreach ($vcards as $id => $vcard){ 
			$addresses = $vcard->addresses();

		?>
		<tr>
			<td><?= (string)$vcard->name() ?></td>
			<td><?= (!empty($addresses))?array_shift($addresses)->get():'' ?></td>
			<td>
				<a class="symbol" title="download" href="<?= $id?>/download"></a>
				<a class="symbol" title="edit" href="<?= $id?>/edit"></a>
				<a class="symbol" title="assign with me" href="<?= $id?>/assign_with_me"></a>
			</td>
		</tr>
		<?php while (!empty($addresses)) { ?>
		<tr>
			<td></td>
			<td><?= (!empty($addresses))?array_shift($addresses)->get():'' ?></td>
			<td></td>
		</tr>
		<?php } ?>
		<?php } ?>
	</table>
</fieldset>
<?php include '../common_templates/closure.php'; ?>
